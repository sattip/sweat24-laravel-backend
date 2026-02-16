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
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Measurement date
            $table->decimal('weight', 5, 2)->nullable()->comment('Weight in kg');
            $table->decimal('height', 5, 2)->nullable()->comment('Height in cm');
            $table->decimal('waist', 5, 2)->nullable()->comment('Waist in cm');
            $table->decimal('hips', 5, 2)->nullable()->comment('Hips in cm');
            $table->decimal('chest', 5, 2)->nullable()->comment('Chest in cm');
            $table->decimal('arm', 5, 2)->nullable()->comment('Arm in cm');
            $table->decimal('thigh', 5, 2)->nullable()->comment('Thigh in cm');
            $table->decimal('body_fat', 5, 2)->nullable()->comment('Body fat percentage');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // One measurement per week per user
            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};