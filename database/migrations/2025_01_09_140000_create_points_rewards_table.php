<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points_cost');
            $table->enum('reward_type', ['gift_card', 'free_session', 'product', 'discount', 'premium', 'merchandise']);
            $table->string('reward_value'); // e.g., "5€", "1 μάθημα", "20%"
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_redemptions')->nullable(); // Max times this reward can be redeemed in total
            $table->integer('current_redemptions')->default(0); // Current count of redemptions
            $table->integer('sort_order')->default(0);
            $table->text('terms_conditions')->nullable();
            $table->timestamp('expires_at')->nullable(); // When the reward itself expires
            $table->timestamps();

            $table->index(['is_active', 'points_cost']);
            $table->index('reward_type');
            $table->index(['sort_order', 'points_cost']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_rewards');
    }
};
