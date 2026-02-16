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
        Schema::create('partner_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_business_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['percentage', 'fixed_amount', 'free_item', 'custom']);
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->string('discount_unit')->nullable(); // %, €, etc.
            $table->string('promo_code')->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('total_usage_limit')->nullable();
            $table->integer('current_usage_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_offers');
    }
};
