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
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->date('session_date');
            $table->integer('duration_minutes')->default(60);
            $table->enum('session_type', ['personal', 'semi_personal', 'group'])->default('personal');
            $table->integer('intensity')->default(5); // RPE 1-10
            $table->json('muscle_groups')->nullable(); // Array of muscle groups worked
            $table->boolean('includes_cardio')->default(false);
            $table->boolean('includes_cognitive')->default(false);
            $table->boolean('includes_mobility')->default(false);
            $table->boolean('includes_balance')->default(false);
            $table->boolean('includes_functional')->default(false);
            $table->boolean('is_total_body')->default(false);
            $table->decimal('total_volume', 10, 2)->nullable(); // Total kg lifted (sets × reps × weight)
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('trainer_id');
            $table->index('session_date');
            $table->index('session_type');
            $table->index('intensity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
