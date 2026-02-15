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
        Schema::table('priority_booking_settings', function (Blueprint $table) {
            // Add new columns
            $table->integer('priority_booking_window_days')->default(30); // Priority users can book X days ahead
            $table->integer('regular_booking_window_days')->default(14); // Regular users can book X days ahead

            // Rename priority_release_hours to priority_seats_release_hours for clarity
            $table->renameColumn('priority_release_hours', 'priority_seats_release_hours');
        });

        // Set default values
        DB::table('priority_booking_settings')->update([
            'priority_booking_window_days' => 30,
            'regular_booking_window_days' => 14,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('priority_booking_settings', function (Blueprint $table) {
            $table->dropColumn(['priority_booking_window_days', 'regular_booking_window_days']);
            $table->renameColumn('priority_seats_release_hours', 'priority_release_hours');
        });
    }
};
