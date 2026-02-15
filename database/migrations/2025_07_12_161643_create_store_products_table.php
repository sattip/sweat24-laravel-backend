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
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->enum('category', ['supplements', 'apparel', 'accessories', 'equipment']);
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->decimal('original_price', 8, 2)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_products');
    }
};
