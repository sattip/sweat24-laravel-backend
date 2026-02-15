<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for MySQL only - uses proper Laravel Schema builder
     */
    public function up(): void
    {
        // Update payment_installments table
        Schema::table('payment_installments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->change();
        });
        
        // Update cash_register_entries table  
        Schema::table('cash_register_entries', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->change();
        });
        
        // Update business_expenses table
        Schema::table('business_expenses', function (Blueprint $table) {
            $table->string('payment_method')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original column definitions
        Schema::table('payment_installments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->change();
        });
        
        Schema::table('cash_register_entries', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->change();
        });
        
        Schema::table('business_expenses', function (Blueprint $table) {
            $table->string('payment_method')->change();
        });
    }
};