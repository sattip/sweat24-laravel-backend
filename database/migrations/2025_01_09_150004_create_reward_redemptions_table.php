<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reward_id')->constrained('points_rewards')->onDelete('cascade');
            $table->integer('points_spent');
            $table->string('reward_code', 100)->nullable();
            $table->enum('status', ['pending', 'active', 'used', 'expired', 'cancelled'])->default('active');
            $table->text('instructions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique('reward_code');
            $table->index(['user_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['reward_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
