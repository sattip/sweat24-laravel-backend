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
        Schema::create('fitness_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('instructor');
            $table->date('date');
            $table->time('time');
            $table->integer('duration')->default(60); // Duration in minutes
            $table->integer('max_participants')->default(20);
            $table->integer('current_participants')->default(0);
            $table->unsignedBigInteger('store_id')->nullable(); // Reference to stores table
            $table->text('location')->nullable(); // Keep for backward compatibility or manual entry
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly
            $table->integer('recurrence_interval')->default(1); // every X days/weeks/months
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['date', 'time']);
            $table->index(['instructor']);
            $table->index(['status']);
            $table->index(['store_id']);

            // store_id foreign key added in later migration when stores table exists
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fitness_classes');
    }
};
