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
        Schema::table('packages', function (Blueprint $table) {
            // Class/lesson type this package is for
            $table->string('class_type')->nullable();

            // Time restriction settings
            $table->boolean('time_restriction_enabled')->default(false);
            $table->time('booking_start_time')->nullable();
            $table->time('booking_end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['class_type', 'time_restriction_enabled', 'booking_start_time', 'booking_end_time']);
        });
    }
};
