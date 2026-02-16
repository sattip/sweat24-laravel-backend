<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class ComprehensiveCashRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('ComprehensiveCashRegisterSeeder skipped - CashRegister model not available.');
        $this->command->info('Using existing CashRegisterEntry model instead.');
    }
}
