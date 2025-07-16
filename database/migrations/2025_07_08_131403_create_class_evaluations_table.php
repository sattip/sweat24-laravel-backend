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
        Schema::create('class_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('gym_classes')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->string('evaluation_token')->unique(); // For anonymous access
            $table->integer('overall_rating')->unsigned(); // 1-5 stars
            $table->integer('instructor_rating')->unsigned(); // 1-5 stars
            $table->integer('facility_rating')->unsigned(); // 1-5 stars
            $table->text('comments')->nullable();
            $table->json('tags')->nullable(); // Quick feedback tags
            $table->boolean('would_recommend')->default(true);
            $table->boolean('is_submitted')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('evaluation_token');
            $table->index(['class_id', 'is_submitted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_evaluations');
    }
};
