<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if we're using SQLite (development) or MySQL (production)
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL supports ENUM modification
            DB::statement("ALTER TABLE payment_installments MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer', 'iris', 'cash_a') NULL");
            DB::statement("ALTER TABLE cash_register_entries MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer', 'iris', 'cash_a') NULL");
            DB::statement("ALTER TABLE business_expenses MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer', 'iris', 'cash_a') NOT NULL");
        } else {
            // For SQLite, we need to recreate the columns
            // Since SQLite doesn't have ENUM, it uses CHECK constraints which we can't easily modify
            // The validation in the controllers will handle the allowed values
            
            // For SQLite, the columns are already TEXT type and don't need modification
            // The validation rules in the controllers will ensure only valid values are saved
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // Revert MySQL ENUMs
            DB::statement("ALTER TABLE payment_installments MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer') NULL");
            DB::statement("ALTER TABLE cash_register_entries MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer') NULL");
            DB::statement("ALTER TABLE business_expenses MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer') NOT NULL");
        }
        // For SQLite, no changes needed as we didn't modify the structure
    }
};