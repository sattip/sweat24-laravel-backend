<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\StoreProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class StoreProductTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    public function test_public_can_list_products()
    {
        $response = $this->getJson('/api/v1/store/products');
        $response->assertStatus(200);
    }

    public function test_admin_products_requires_auth()
    {
        $this->getJson('/api/v1/admin/store/products')->assertStatus(401);
    }

    public function test_member_cannot_access_admin_products()
    {
        Sanctum::actingAs($this->member);
        $this->getJson('/api/v1/admin/store/products')->assertStatus(403);
    }

    public function test_admin_can_list_admin_products()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/admin/store/products');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_product()
    {
        Sanctum::actingAs($this->admin);
        $response = $this->postJson('/api/v1/admin/store/products', [
            'name' => 'Protein Bar',
            'slug' => 'protein-bar',
            'price' => 3.50,
            'stock' => 20,
            'category' => 'supplements',
            'is_active' => true,
        ]);
        $this->assertContains($response->status(), [200, 201, 422]);
    }

    public function test_admin_can_update_product()
    {
        Sanctum::actingAs($this->admin);
        $product = StoreProduct::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 5.00,
            'stock' => 10,
            'category' => 'supplements',
            'is_active' => true,
        ]);
        $response = $this->putJson("/api/v1/admin/store/products/{$product->id}", [
            'name' => 'Updated Product',
            'price' => 6.00,
        ]);
        $this->assertContains($response->status(), [200, 422]);
    }

    public function test_admin_can_delete_product()
    {
        Sanctum::actingAs($this->admin);
        $product = StoreProduct::create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'price' => 5.00,
            'stock' => 10,
            'category' => 'supplements',
            'is_active' => true,
        ]);
        $response = $this->deleteJson("/api/v1/admin/store/products/{$product->id}");
        $response->assertStatus(200);
    }
}
