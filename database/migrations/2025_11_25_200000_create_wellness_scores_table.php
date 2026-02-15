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
        Schema::create('wellness_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date'); // One entry per user per day

            // Sleep tracking
            $table->decimal('sleep_hours', 4, 2)->nullable(); // e.g., 7.50 hours
            $table->string('sleep_quality')->nullable(); // poor, fair, good, excellent

            // Hydration tracking
            $table->decimal('hydration_liters', 4, 2)->nullable(); // e.g., 2.50 liters

            // Calorie tracking
            $table->integer('calories_consumed')->nullable(); // actual calories
            $table->integer('tdee')->nullable(); // Total Daily Energy Expenditure
            $table->decimal('calories_percentage', 5, 2)->nullable(); // % of TDEE

            // Subjective metrics (1-10 scale)
            $table->tinyInteger('energy_level')->nullable(); // 1-10
            $table->tinyInteger('mood_level')->nullable(); // 1-10
            $table->tinyInteger('stress_level')->nullable(); // 1-10
            $table->tinyInteger('soreness_level')->nullable(); // 1-10 (muscle soreness)

            // HRV if available from wearable
            $table->integer('hrv')->nullable(); // Heart Rate Variability in ms

            // Calculated scores
            $table->integer('wellness_score')->nullable(); // Overall 0-100 score

            // Alert levels for each metric
            $table->string('sleep_alert')->nullable(); // green, orange, red
            $table->string('hydration_alert')->nullable();
            $table->string('calories_alert')->nullable();
            $table->string('overall_alert')->nullable(); // Combined alert status

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // One entry per user per day
            $table->unique(['user_id', 'date']);

            // Indexes for queries
            $table->index('date');
            $table->index(['user_id', 'date']);
            $table->index('wellness_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_scores');
    }
};
