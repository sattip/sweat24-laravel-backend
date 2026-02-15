<?php

namespace Tests\Unit\Models;

use App\Models\StoreProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_store_product(): void
    {
        $product = StoreProduct::factory()->create();
        $this->assertDatabaseHas('store_products', ['id' => $product->id]);
    }

    public function test_supplements_state(): void
    {
        $product = StoreProduct::factory()->supplements()->create();
        $this->assertEquals('supplements', $product->category);
    }

    public function test_apparel_state(): void
    {
        $product = StoreProduct::factory()->apparel()->create();
        $this->assertEquals('apparel', $product->category);
    }

    public function test_accessories_state(): void
    {
        $product = StoreProduct::factory()->accessories()->create();
        $this->assertEquals('accessories', $product->category);
    }

    public function test_equipment_state(): void
    {
        $product = StoreProduct::factory()->equipment()->create();
        $this->assertEquals('equipment', $product->category);
    }

    public function test_active_state(): void
    {
        $product = StoreProduct::factory()->active()->create();
        $this->assertTrue($product->is_active);
    }

    public function test_inactive_state(): void
    {
        $product = StoreProduct::factory()->inactive()->create();
        $this->assertFalse($product->is_active);
    }

    public function test_preorder_state(): void
    {
        $product = StoreProduct::factory()->preorder()->create();
        $this->assertEquals(0, $product->stock_quantity);
    }

    public function test_on_sale_state(): void
    {
        $product = StoreProduct::factory()->onSale()->create();

        $this->assertNotNull($product->original_price);
        $this->assertGreaterThan($product->price, $product->original_price);
    }

    public function test_out_of_stock_state(): void
    {
        $product = StoreProduct::factory()->outOfStock()->create();
        $this->assertEquals(0, $product->stock_quantity);
    }

    public function test_in_stock_state(): void
    {
        $product = StoreProduct::factory()->inStock()->create();
        $this->assertGreaterThan(0, $product->stock_quantity);
    }

    public function test_has_unique_slug(): void
    {
        $product1 = StoreProduct::factory()->create();
        $product2 = StoreProduct::factory()->create();

        $this->assertNotEquals($product1->slug, $product2->slug);
    }

    public function test_category_is_valid(): void
    {
        $product = StoreProduct::factory()->create();
        $validCategories = ['supplements', 'apparel', 'accessories', 'equipment'];

        $this->assertContains($product->category, $validCategories);
    }
}
