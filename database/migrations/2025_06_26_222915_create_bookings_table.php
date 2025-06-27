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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('class_name');
            $table->string('instructor');
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['confirmed', 'pending', 'cancelled', 'waitlist', 'completed'])->default('pending');
            $table->enum('type', ['group', 'personal']);
            $table->boolean('attended')->nullable();
            $table->timestamp('booking_time');
            $table->string('location');
            $table->string('avatar')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
