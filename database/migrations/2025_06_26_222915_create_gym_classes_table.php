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
        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('instructor');
            $table->date('date');
            $table->time('time');
            $table->integer('duration'); // minutes
            $table->integer('max_participants');
            $table->integer('current_participants')->default(0);
            $table->string('location');
            $table->text('description');
            $table->enum('status', ['active', 'cancelled', 'booked'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gym_classes');
    }
};
