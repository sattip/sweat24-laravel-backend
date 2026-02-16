<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin Demo Account
        User::updateOrCreate(
            ['email' => 'admin@sweat24.gr'],
            [
                'name' => 'Admin Demo User',
                'password' => Hash::make('password'),
                'phone' => '+30 210 1234567',
                'address' => 'Demo Admin Address',
                'membership_type' => 'Admin',
                'role' => 'admin',
                'join_date' => now(),
                'status' => 'active',
                'remaining_sessions' => 0,
                'total_sessions' => 0,
                'notes' => 'Demo admin account for testing'
            ]
        );

        // Create Member Demo Account
        User::updateOrCreate(
            ['email' => 'user@sweat24.gr'],
            [
                'name' => 'Member Demo User',
                'password' => Hash::make('password'),
                'phone' => '+30 210 7654321',
                'address' => 'Demo Member Address',
                'membership_type' => 'Premium',
                'role' => 'member',
                'join_date' => now()->subMonths(3),
                'status' => 'active',
                'remaining_sessions' => 10,
                'total_sessions' => 25,
                'notes' => 'Demo member account for testing'
            ]
        );

        $this->command->info('Demo accounts created successfully!');
        $this->command->table(
            ['Name', 'Email', 'Password', 'Role'],
            [
                ['Admin Demo User', 'admin@sweat24.gr', 'password', 'admin'],
                ['Member Demo User', 'user@sweat24.gr', 'password', 'member'],
            ]
        );
    }
} 