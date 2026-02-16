<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points_balance')->default(0);
            $table->integer('total_earned')->default(0);
            $table->integer('total_spent')->default(0);
            $table->integer('lifetime_rank')->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('points_balance');
            $table->index('total_earned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_points');
    }
};
