<?php

namespace Database\Seeders;

use App\Models\StoreProduct;
use Illuminate\Database\Seeder;

class StoreProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create store products based on client app mock data
        $products = [
            [
                'name' => 'Πρωτεΐνη Premium',
                'price' => 59.99,
                'description' => 'Υψηλής ποιότητας πρωτεΐνη ορού για βέλτιστη ανάκαμψη των μυών.',
                'image_url' => 'https://images.unsplash.com/photo-1582562124811-c09040d0a901?w=400&h=400&fit=crop',
                'category' => 'supplements',
                'stock_quantity' => 50,
                'display_order' => 1,
            ],
            [
                'name' => 'T-Shirt Απόδοσης',
                'price' => 34.99,
                'description' => 'Ύφασμα που απορροφά την υγρασία για έντονες προπονήσεις.',
                'image_url' => 'https://images.unsplash.com/photo-1618160702438-9b02ab6515c9?w=400&h=400&fit=crop',
                'category' => 'apparel',
                'stock_quantity' => 30,
                'display_order' => 2,
            ],
            [
                'name' => 'Μπουκάλι Νερού Sweat24',
                'price' => 24.99,
                'description' => 'Μπουκάλι νερού 1L χωρίς BPA με μετρήσεις.',
                'image_url' => 'https://images.unsplash.com/photo-1535268647677-300dbf3d78d1?w=400&h=400&fit=crop',
                'category' => 'accessories',
                'stock_quantity' => 40,
                'display_order' => 3,
            ],
            [
                'name' => 'Φόρμουλα Pre-Workout',
                'price' => 49.99,
                'description' => 'Φόρμουλα ενίσχυσης ενέργειας για μεγιστοποίηση της προπόνησης.',
                'image_url' => 'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?w=400&h=400&fit=crop',
                'category' => 'supplements',
                'stock_quantity' => 35,
                'display_order' => 4,
            ],
            [
                'name' => 'Κολάν Συμπίεσης',
                'price' => 29.99,
                'description' => 'Υποστηρικτικό κολάν συμπίεσης για υψηλής έντασης προπόνηση.',
                'image_url' => 'https://images.unsplash.com/photo-1618160702438-9b02ab6515c9?w=400&h=400&fit=crop',
                'category' => 'apparel',
                'stock_quantity' => 25,
                'display_order' => 5,
            ],
            [
                'name' => 'Γάντια Γυμναστικής',
                'price' => 19.99,
                'description' => 'Γάντια ενισχυμένα με καουτσούκ για καλύτερο κράτημα.',
                'image_url' => 'https://images.unsplash.com/photo-1564415315949-7a0c4c73e79b?w=400&h=400&fit=crop',
                'category' => 'accessories',
                'stock_quantity' => 45,
                'display_order' => 6,
            ],
            [
                'name' => 'Στρώμα Yoga',
                'price' => 39.99,
                'description' => 'Αντιολισθητικό στρώμα γυμναστικής 6mm.',
                'image_url' => 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=400&h=400&fit=crop',
                'category' => 'equipment',
                'stock_quantity' => 20,
                'display_order' => 7,
            ],
            [
                'name' => 'BCAA Κάψουλες',
                'price' => 44.99,
                'description' => 'Αμινοξέα διακλαδισμένης αλυσίδας για μυϊκή ανάκαμψη.',
                'image_url' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=400&fit=crop',
                'category' => 'supplements',
                'stock_quantity' => 55,
                'display_order' => 8,
            ],
            [
                'name' => 'Ζώνη Άρσης Βαρών',
                'price' => 54.99,
                'description' => 'Δερμάτινη ζώνη υποστήριξης για άρση βαρών.',
                'image_url' => 'https://images.unsplash.com/photo-1516208813382-cbaf53501038?w=400&h=400&fit=crop',
                'category' => 'equipment',
                'stock_quantity' => 15,
                'display_order' => 9,
            ],
            [
                'name' => 'Tank Top Sweat24',
                'price' => 27.99,
                'description' => 'Αθλητικό tank top με το λογότυπο Sweat24.',
                'image_url' => 'https://images.unsplash.com/photo-1618453292507-4959ebe6abb2?w=400&h=400&fit=crop',
                'category' => 'apparel',
                'stock_quantity' => 35,
                'display_order' => 10,
            ],
        ];

        foreach ($products as $productData) {
            StoreProduct::updateOrCreate(
                ['name' => $productData['name']],
                $productData
            );
        }

        $this->command->info('Store products created successfully!');
    }
}