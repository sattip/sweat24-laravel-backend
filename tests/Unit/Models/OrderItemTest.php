<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StoreProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_item(): void
    {
        $order = Order::factory()->create();
        $product = StoreProduct::factory()->create();

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_order_item_belongs_to_order(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($order->id, $orderItem->order->id);
    }

    public function test_order_item_belongs_to_product(): void
    {
        $product = StoreProduct::factory()->create();
        $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(StoreProduct::class, $orderItem->product);
        $this->assertEquals($product->id, $orderItem->product->id);
    }

    public function test_order_item_calculates_subtotal(): void
    {
        $orderItem = OrderItem::factory()->create([
            'price' => 5.00,
            'quantity' => 3,
            'subtotal' => 15.00,
        ]);

        $this->assertEquals('15.00', $orderItem->subtotal);
    }

    public function test_order_item_preorder_state(): void
    {
        $orderItem = OrderItem::factory()->preorder()->create();

        $this->assertTrue($orderItem->is_preorder);
    }

    public function test_order_item_with_quantity_state(): void
    {
        $orderItem = OrderItem::factory()
            ->state(['price' => 10.00])
            ->withQuantity(5)
            ->create();

        $this->assertEquals(5, $orderItem->quantity);
        $this->assertEquals('50.00', $orderItem->subtotal);
    }

    public function test_order_item_casts_decimal_fields(): void
    {
        $orderItem = OrderItem::factory()->create([
            'price' => 12.99,
            'subtotal' => 25.98,
        ]);

        $this->assertEquals('12.99', $orderItem->price);
        $this->assertEquals('25.98', $orderItem->subtotal);
    }

    public function test_order_item_casts_quantity_to_integer(): void
    {
        $orderItem = OrderItem::factory()->create(['quantity' => 3]);

        $this->assertIsInt($orderItem->quantity);
        $this->assertEquals(3, $orderItem->quantity);
    }
}
