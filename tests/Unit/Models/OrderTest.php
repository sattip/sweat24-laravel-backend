<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_order_generates_order_number_on_creation(): void
    {
        $order = Order::factory()->create();

        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_order_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_has_many_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }

    public function test_pending_order_is_editable(): void
    {
        $order = Order::factory()->pending()->create();

        $this->assertTrue($order->isEditable());
    }

    public function test_processing_order_is_editable(): void
    {
        $order = Order::factory()->processing()->create();

        $this->assertTrue($order->isEditable());
    }

    public function test_completed_order_is_not_editable(): void
    {
        $order = Order::factory()->completed()->create();

        $this->assertFalse($order->isEditable());
    }

    public function test_cancelled_order_is_not_editable(): void
    {
        $order = Order::factory()->cancelled()->create();

        $this->assertFalse($order->isEditable());
    }

    public function test_can_mark_order_as_ready(): void
    {
        $order = Order::factory()->processing()->create();

        $order->markAsReady();
        $order->refresh();

        $this->assertEquals('ready_for_pickup', $order->status);
        $this->assertNotNull($order->ready_at);
    }

    public function test_can_mark_order_as_completed(): void
    {
        $order = Order::factory()->readyForPickup()->create();

        $order->markAsCompleted();
        $order->refresh();

        $this->assertEquals('completed', $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_can_cancel_order(): void
    {
        $order = Order::factory()->pending()->create();

        $order->cancel();
        $order->refresh();

        $this->assertEquals('cancelled', $order->status);
    }

    public function test_order_without_points_applied(): void
    {
        $order = Order::factory()->create(['points_applied' => false]);

        $this->assertFalse($order->hasPointsApplied());
    }

    public function test_order_with_points_applied(): void
    {
        $order = Order::factory()->withPoints()->create();

        $this->assertTrue($order->hasPointsApplied());
    }

    public function test_preorder_state(): void
    {
        $order = Order::factory()->preorder()->create();

        $this->assertTrue($order->is_preorder);
    }

    public function test_order_casts_decimal_fields(): void
    {
        $order = Order::factory()->create([
            'subtotal' => 10.50,
            'tax' => 2.52,
            'total' => 13.02,
        ]);

        $this->assertEquals('10.50', $order->subtotal);
        $this->assertEquals('2.52', $order->tax);
        $this->assertEquals('13.02', $order->total);
    }

    public function test_order_casts_datetime_fields(): void
    {
        $order = Order::factory()->completed()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $order->ready_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $order->completed_at);
    }
}
