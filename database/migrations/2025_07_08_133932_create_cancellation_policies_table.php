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
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('hours_before')->unsigned(); // Hours before class to cancel
            $table->decimal('penalty_percentage', 5, 2)->default(0); // 0-100%
            $table->boolean('allow_reschedule')->default(true);
            $table->integer('reschedule_hours_before')->unsigned()->nullable();
            $table->integer('max_reschedules_per_month')->default(3);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // For ordering policies
            $table->json('applicable_to')->nullable(); // class types, packages, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};
