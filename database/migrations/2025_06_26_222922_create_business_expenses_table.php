<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['utilities', 'equipment', 'maintenance', 'supplies', 'marketing', 'other']);
            $table->string('subcategory');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('vendor')->nullable();
            $table->string('receipt')->nullable(); // URL to receipt
            $table->enum('payment_method', ['cash', 'card', 'transfer']);
            $table->boolean('approved')->default(false);
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_expenses');
    }
};
