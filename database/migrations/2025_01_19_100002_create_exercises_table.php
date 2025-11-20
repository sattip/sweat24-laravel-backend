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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_gr');
            $table->string('muscle_group');
            $table->string('category');
            $table->json('equipment')->nullable(); // Array of equipment
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('muscle_group');
            $table->index('category');
            $table->index('difficulty_level');
            $table->index('is_active');
            $table->index('name_en');
            $table->index('name_gr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
