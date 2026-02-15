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
        Schema::create('training_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->nullable()->constrained()->onDelete('set null');
            $table->string('exercise_name')->nullable(); // Fallback if exercise is deleted or custom
            $table->integer('sets')->default(3);
            $table->integer('reps')->default(10);
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->integer('rest_seconds')->nullable();
            $table->string('tempo')->nullable(); // e.g., "3-1-1-0"
            $table->integer('rir')->nullable(); // Reps in Reserve
            $table->enum('exercise_type', [
                'standard',
                'superset',
                'drop',
                'pyramid',
                'emom',
                'amrap',
                'failure',
                'timed'
            ])->default('standard');
            $table->string('superset_with')->nullable(); // For tracking superset pairs
            $table->text('notes')->nullable();
            $table->integer('order')->default(0); // Order within the session
            $table->timestamps();

            // Indexes
            $table->index('training_session_id');
            $table->index('exercise_id');
            $table->index('exercise_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_exercises');
    }
};
