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
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->integer('priority_seats')->default(0);
            $table->integer('priority_seats_booked')->default(0);
            $table->timestamp('priority_seats_release_at')->nullable();
            $table->boolean('priority_booking_enabled')->default(true);
            $table->index('priority_seats_release_at');
            $table->index('priority_booking_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->dropColumn([
                'priority_seats',
                'priority_seats_booked',
                'priority_seats_release_at',
                'priority_booking_enabled'
            ]);
        });
    }
};