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
        Schema::table('booking_requests', function (Blueprint $table) {
            // Rename preferred_dates to preferred_time_slots for clarity
            $table->renameColumn('preferred_dates', 'preferred_time_slots');
            
            // The preferred_times column will now store an array of time slot objects
            // Each object will have: {date: 'YYYY-MM-DD', start_time: 'HH:MM', end_time: 'HH:MM'}
            // We don't need to change the column type as it's already JSON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->renameColumn('preferred_time_slots', 'preferred_dates');
        });
    }
};
