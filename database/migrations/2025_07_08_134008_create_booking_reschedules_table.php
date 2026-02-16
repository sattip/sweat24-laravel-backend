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
        Schema::create('booking_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('original_class_id')->constrained('gym_classes');
            $table->foreignId('new_class_id')->nullable()->constrained('gym_classes');
            $table->dateTime('original_datetime');
            $table->dateTime('new_datetime')->nullable();
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed']);
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'requested_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_reschedules');
    }
};