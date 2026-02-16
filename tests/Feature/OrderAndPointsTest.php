<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\StoreProduct;
use App\Models\UserPoints;
use App\Services\PointsService;

class OrderAndPointsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    private function createMember(): array
    {
        $user = User::factory()->create(['role' => 'member']);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    public function test_order_stock_atomic_decrement(): void
    {
        $product = StoreProduct::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 5.00,
            'stock_quantity' => 1,
            'is_active' => true,
            'category' => 'supplements',
        ]);

        // First decrement should succeed
        $updated = StoreProduct::where('id', $product->id)
            ->where('stock_quantity', '>=', 1)
            ->decrement('stock_quantity');

        $this->assertEquals(1, $updated);
        $this->assertEquals(0, $product->fresh()->stock_quantity);

        // Second decrement should fail atomically
        $updated2 = StoreProduct::where('id', $product->id)
            ->where('stock_quantity', '>=', 1)
            ->decrement('stock_quantity');

        $this->assertEquals(0, $updated2);
        $this->assertEquals(0, $product->fresh()->stock_quantity);
    }

    public function test_order_creation_requires_valid_user(): void
    {
        $product = StoreProduct::create([
            'name' => 'Test Product',
            'slug' => 'test-product-2',
            'price' => 5.00,
            'stock_quantity' => 10,
            'is_active' => true,
            'category' => 'supplements',
        ]);

        $response = $this->postJson('/api/v1/orders', [
            'user_id' => 99999,
            'customer_name' => 'Ghost',
            'customer_email' => 'ghost@test.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_points_award_atomic_prevents_double(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'completed',
            'subtotal' => 80.00,
            'tax' => 19.20,
            'total' => 99.20,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'points_applied' => false,
        ]);

        $pointsService = app(PointsService::class);

        // First call should award points
        $result1 = $pointsService->awardPointsForOrder($order);
        $this->assertTrue($result1);

        // Second call should NOT award points (already applied)
        $order->refresh();
        $result2 = $pointsService->awardPointsForOrder($order);
        $this->assertFalse($result2);

        // Verify points were only awarded once
        $userPoints = UserPoints::where('user_id', $user->id)->first();
        $this->assertEquals(99, $userPoints->points_balance); // floor(99.20)
    }

    public function test_order_index_requires_auth_not_origin(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost:5174')
            ->getJson('/api/v1/orders');

        $response->assertStatus(401);
    }

    public function test_order_show_prevents_idor(): void
    {
        [$userA, $tokenA] = $this->createMember();
        [$userB, $tokenB] = $this->createMember();

        $orderB = Order::create([
            'user_id' => $userB->id,
            'status' => 'pending',
            'subtotal' => 10,
            'tax' => 2.4,
            'total' => 12.4,
            'customer_name' => $userB->name,
            'customer_email' => $userB->email,
        ]);

        // User A tries to view User B's order
        $response = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson("/api/v1/orders/{$orderB->id}");

        $response->assertStatus(403);
    }

    public function test_order_status_update_requires_admin(): void
    {
        [$member, $token] = $this->createMember();

        $order = Order::create([
            'user_id' => $member->id,
            'status' => 'pending',
            'subtotal' => 10,
            'tax' => 2.4,
            'total' => 12.4,
            'customer_name' => $member->name,
            'customer_email' => $member->email,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(403);
    }

    public function test_cancelled_order_restores_stock(): void
    {
        [$admin, $token] = $this->createAdmin();

        $product = StoreProduct::create([
            'name' => 'Test Restore',
            'slug' => 'test-restore',
            'price' => 5.00,
            'stock_quantity' => 10,
            'is_active' => true,
            'category' => 'supplements',
        ]);

        // Create order (stock goes from 10 to 8)
        $order = Order::create([
            'user_id' => $admin->id,
            'status' => 'pending',
            'subtotal' => 10,
            'tax' => 2.4,
            'total' => 12.4,
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 2,
            'subtotal' => 10,
            'is_preorder' => false,
        ]);

        $product->decrement('stock_quantity', 2);
        $this->assertEquals(8, $product->fresh()->stock_quantity);

        // Cancel order
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ]);

        $response->assertOk();
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }
}
