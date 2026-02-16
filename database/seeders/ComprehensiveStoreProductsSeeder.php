<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StoreProduct;

class ComprehensiveStoreProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Check if products already exist
        if (StoreProduct::count() > 0) {
            $this->command->info('Store products already exist. Skipping...');
            return;
        }

        // Skip location assignment for now (no Location model exists)
        $this->command->info('Creating store products without location assignment...');

        $products = [
            // Supplements
            [
                'name' => 'Whey Protein 1kg',
                'description' => 'Premium whey protein powder',
                'category' => 'supplements',
                'price' => 45.00,
                'original_price' => 55.00,
                'stock_quantity' => 20,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Pre-Workout Boost',
                'description' => 'Energy and focus pre-workout supplement',
                'category' => 'supplements',
                'price' => 35.00,
                'original_price' => 40.00,
                'stock_quantity' => 15,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'BCAA Complex',
                'description' => 'Branch-chain amino acids supplement',
                'category' => 'supplements',
                'price' => 28.00,
                'original_price' => 35.00,
                'stock_quantity' => 18,
                'is_active' => true,
                'display_order' => 3,
            ],
            
            // Apparel
            [
                'name' => 'Sweat93 T-Shirt',
                'description' => 'Official Sweat93 branded t-shirt',
                'category' => 'apparel',
                'price' => 25.00,
                'original_price' => 30.00,
                'stock_quantity' => 50,
                'is_active' => true,
                'display_order' => 4,
            ],
            [
                'name' => 'Training Shorts',
                'description' => 'Comfortable training shorts',
                'category' => 'apparel',
                'price' => 22.00,
                'original_price' => 28.00,
                'stock_quantity' => 40,
                'is_active' => true,
                'display_order' => 5,
            ],
            
            // Accessories
            [
                'name' => 'Protein Shaker',
                'description' => 'High-quality protein shaker bottle',
                'category' => 'accessories',
                'price' => 12.00,
                'original_price' => 15.00,
                'stock_quantity' => 30,
                'is_active' => true,
                'display_order' => 6,
            ],
            [
                'name' => 'Gym Towel',
                'description' => 'Premium microfiber gym towel',
                'category' => 'accessories',
                'price' => 18.00,
                'original_price' => 22.00,
                'stock_quantity' => 25,
                'is_active' => true,
                'display_order' => 7,
            ],
            [
                'name' => 'Water Bottle',
                'description' => 'Insulated water bottle',
                'category' => 'accessories',
                'price' => 15.00,
                'original_price' => 20.00,
                'stock_quantity' => 35,
                'is_active' => true,
                'display_order' => 8,
            ],
            
            // Equipment
            [
                'name' => 'Resistance Bands Set',
                'description' => 'Set of 5 resistance bands with different strengths',
                'category' => 'equipment',
                'price' => 32.00,
                'original_price' => 40.00,
                'stock_quantity' => 20,
                'is_active' => true,
                'display_order' => 9,
            ],
            [
                'name' => 'Yoga Mat',
                'description' => 'High-quality yoga and exercise mat',
                'category' => 'equipment',
                'price' => 28.00,
                'original_price' => 35.00,
                'stock_quantity' => 25,
                'is_active' => true,
                'display_order' => 10,
            ],
        ];

        foreach ($products as $productData) {
            StoreProduct::create($productData);
        }

        $this->command->info('Created ' . count($products) . ' store products successfully!');
    }
}
