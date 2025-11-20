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
        Schema::create('performance_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('exercise_name');
            $table->date('test_date');
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->integer('reps')->nullable();
            $table->integer('time_seconds')->nullable();
            $table->enum('category', ['strength', 'core', 'endurance', 'cognitive'])->default('strength');
            $table->boolean('is_pr')->default(false);
            $table->decimal('improvement_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('test_date');
            $table->index('exercise_name');
            $table->index('category');
            $table->index('is_pr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_tests');
    }
};
