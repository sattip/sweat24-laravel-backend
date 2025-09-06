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
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate the tables to update the CHECK constraints
            
            // 1. Handle cash_register_entries table
            Schema::table('cash_register_entries', function (Blueprint $table) {
                // Drop the foreign key constraints temporarily
                $table->dropForeign(['user_id']);
            });
            
            // Create a new table with updated constraints
            Schema::create('cash_register_entries_new', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['income', 'withdrawal']);
                $table->decimal('amount', 10, 2);
                $table->text('description');
                $table->string('category');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('payment_method')->nullable();
                $table->string('related_entity_id')->nullable();
                $table->enum('related_entity_type', ['customer', 'package', 'expense', 'other'])->nullable();
                $table->timestamps();
            });
            
            // Copy data from old table to new table
            DB::statement('INSERT INTO cash_register_entries_new SELECT * FROM cash_register_entries');
            
            // Drop old table and rename new table
            Schema::dropIfExists('cash_register_entries');
            Schema::rename('cash_register_entries_new', 'cash_register_entries');
            
            // 2. Handle payment_installments table
            Schema::table('payment_installments', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropForeign(['package_id']);
            });
            
            Schema::create('payment_installments_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
                $table->string('customer_name');
                $table->foreignId('package_id')->constrained()->onDelete('cascade');
                $table->string('package_name');
                $table->integer('installment_number');
                $table->integer('total_installments');
                $table->decimal('amount', 8, 2);
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
            
            DB::statement('INSERT INTO payment_installments_new SELECT * FROM payment_installments');
            Schema::dropIfExists('payment_installments');
            Schema::rename('payment_installments_new', 'payment_installments');
            
            // 3. Handle business_expenses table
            Schema::create('business_expenses_new', function (Blueprint $table) {
                $table->id();
                $table->enum('category', ['utilities', 'equipment', 'maintenance', 'supplies', 'marketing', 'other']);
                $table->string('subcategory');
                $table->text('description');
                $table->decimal('amount', 10, 2);
                $table->date('date');
                $table->string('vendor')->nullable();
                $table->string('receipt')->nullable();
                $table->string('payment_method');
                $table->boolean('approved')->default(false);
                $table->string('approved_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
            
            DB::statement('INSERT INTO business_expenses_new SELECT * FROM business_expenses');
            Schema::dropIfExists('business_expenses');
            Schema::rename('business_expenses_new', 'business_expenses');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible for SQLite
        // The previous migration already handles MySQL
    }
};