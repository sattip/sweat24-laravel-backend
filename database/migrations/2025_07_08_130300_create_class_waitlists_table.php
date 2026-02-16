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
        Schema::create('class_waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('gym_classes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('position'); // Position in waitlist
            $table->enum('status', ['waiting', 'notified', 'confirmed', 'expired'])->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Ensure unique user per class in waitlist
            $table->unique(['class_id', 'user_id']);
            $table->index(['class_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_waitlists');
    }
};