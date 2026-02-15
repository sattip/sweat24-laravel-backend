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
        Schema::create('questionnaire_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionnaire_id');
            $table->unsignedBigInteger('user_id');
            $table->jsonb('responses'); // User's answers
            $table->string('trigger_type'); // e.g., 'after_lesson', 'daily', etc.
            $table->timestamp('completed_at')->useCurrent();
            $table->string('session_id')->nullable(); // For tracking sessions
            $table->timestamps();

            $table->foreign('questionnaire_id')->references('id')->on('questionnaires')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['questionnaire_id', 'user_id']);
            $table->index(['trigger_type']);
            $table->index(['completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_responses');
    }
};
