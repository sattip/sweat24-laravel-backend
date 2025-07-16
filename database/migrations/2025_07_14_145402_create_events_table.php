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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->date('date');
            $table->time('time');
            $table->string('location');
            $table->string('image_url')->nullable();
            $table->enum('type', ['social', 'educational', 'fitness', 'other'])->default('other');
            $table->json('details')->nullable(); // Additional details as JSON array
            $table->boolean('is_active')->default(true);
            $table->integer('max_attendees')->nullable();
            $table->integer('current_attendees')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
