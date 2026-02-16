<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CashRegisterEntry;
use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;

class SimpleCashRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run store seeder first.');
            return;
        }

        $entries = [];

        // Create a few simple entries for testing
        foreach ($stores as $store) {
            $entries[] = [
                'type' => 'income',
                'amount' => 50.00,
                'description' => "Test entry for {$store->name}",
                'category' => 'Test',
                'user_id' => 1,
                'payment_method' => 'cash',
                'store_id' => $store->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CashRegisterEntry::insert($entries);

        $this->command->info('Simple cash register entries seeded successfully!');
        $this->command->info('Created ' . count($entries) . ' test entries');
    }
}







