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
        Schema::table('bookings', function (Blueprint $table) {
            // Drop the foreign key constraint on class_id
            // This allows bookings to reference either gym_classes or fitness_classes
            $table->dropForeign(['class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('class_id')->references('id')->on('gym_classes')->onDelete('cascade');
        });
    }
};
