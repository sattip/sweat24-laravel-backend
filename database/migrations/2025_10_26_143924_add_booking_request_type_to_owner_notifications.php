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
        // Check if type column exists before trying to drop it
        if (Schema::hasColumn('owner_notifications', 'type')) {
            Schema::table('owner_notifications', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Add type column with the new booking_request value
        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->enum('type', ['graceful_cancellation', 'package_extension', 'special_price', 'booking_request', 'general'])->default('general')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore to previous enum values
        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('owner_notifications', function (Blueprint $table) {
            $table->enum('type', ['graceful_cancellation', 'package_extension', 'special_price', 'general'])->after('id');
        });
    }
};
