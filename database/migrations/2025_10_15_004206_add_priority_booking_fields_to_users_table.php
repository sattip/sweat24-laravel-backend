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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_priority_booking')->default(false);
            $table->timestamp('priority_booking_expires_at')->nullable();
            $table->integer('priority_booking_hours_advance')->default(48);
            $table->index('has_priority_booking');
            $table->index('priority_booking_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'has_priority_booking',
                'priority_booking_expires_at',
                'priority_booking_hours_advance'
            ]);
        });
    }
};