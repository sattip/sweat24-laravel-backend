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
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('medical_conditions');
            $table->json('current_health_problems');
            $table->json('prescribed_medications');
            $table->json('smoking');
            $table->json('physical_activity');
            $table->json('emergency_contact');
            $table->boolean('liability_declaration_accepted');
            $table->timestamp('submitted_at');
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
