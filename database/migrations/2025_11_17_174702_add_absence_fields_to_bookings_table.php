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
            $table->string('absence_reason')->nullable()->after('cancellation_reason');
            $table->boolean('absence_with_charge')->nullable()->after('absence_reason');
            $table->timestamp('absence_marked_at')->nullable()->after('absence_with_charge');
            $table->unsignedBigInteger('absence_marked_by')->nullable()->after('absence_marked_at');

            // Add foreign key for absence_marked_by
            $table->foreign('absence_marked_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['absence_marked_by']);
            $table->dropColumn(['absence_reason', 'absence_with_charge', 'absence_marked_at', 'absence_marked_by']);
        });
    }
};
