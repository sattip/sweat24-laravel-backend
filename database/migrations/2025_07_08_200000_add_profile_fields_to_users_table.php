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
            $table->string('address', 500)->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('address');
            $table->string('emergency_contact')->nullable()->after('medical_history');
            $table->string('emergency_phone', 20)->nullable()->after('emergency_contact');
            $table->text('notes')->nullable()->after('emergency_phone');
            $table->json('notification_preferences')->nullable()->after('notes');
            $table->json('privacy_settings')->nullable()->after('notification_preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'date_of_birth',
                'emergency_contact',
                'emergency_phone',
                'notes',
                'notification_preferences',
                'privacy_settings'
            ]);
        });
    }
};