<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::updateOrCreate(
            ['email' => 'admin@sweat24.gr'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '+30 210 1234567',
                'membership_type' => 'Admin',
                'status' => 'active',
                'join_date' => now(),
            ]
        );
        
        // Create additional admin users for testing
        $adminUsers = [
            [
                'name' => 'John Administrator',
                'email' => 'john.admin@sweat24.gr',
                'password' => Hash::make('admin123'),
                'phone' => '+30 210 1234568',
            ],
            [
                'name' => 'Maria Manager',
                'email' => 'maria.manager@sweat24.gr',
                'password' => Hash::make('admin123'),
                'phone' => '+30 210 1234569',
            ],
        ];
        
        foreach ($adminUsers as $adminData) {
            User::updateOrCreate(
                ['email' => $adminData['email']],
                array_merge($adminData, [
                    'membership_type' => 'Admin',
                    'status' => 'active',
                    'join_date' => now(),
                ])
            );
        }
        
        $this->command->info('Admin users created successfully!');
        $this->command->table(
            ['Name', 'Email', 'Password'],
            [
                ['Admin User', 'admin@sweat24.gr', 'password'],
                ['John Administrator', 'john.admin@sweat24.gr', 'admin123'],
                ['Maria Manager', 'maria.manager@sweat24.gr', 'admin123'],
            ]
        );
    }
}