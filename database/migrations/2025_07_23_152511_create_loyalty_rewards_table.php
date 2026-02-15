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
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->integer('points_cost'); // Κόστος σε πόντους
            $table->integer('validity_days')->nullable(); // Διάρκεια ισχύος σε ημέρες (null = δεν λήγει)
            $table->string('type')->default('gift'); // 'gift', 'discount', 'service', 'product'
            $table->decimal('discount_percentage', 5, 2)->nullable(); // Για εκπτώσεις
            $table->decimal('discount_amount', 10, 2)->nullable(); // Σταθερή έκπτωση
            $table->integer('max_redemptions')->nullable(); // Μέγιστες εξαργυρώσεις (null = απεριόριστες)
            $table->integer('current_redemptions')->default(0); // Τρέχουσες εξαργυρώσεις
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('terms_conditions')->nullable(); // Όροι και προϋποθέσεις
            $table->timestamps();

            $table->index(['is_active', 'points_cost']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
