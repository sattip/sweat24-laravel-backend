<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('priority_booking_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('default_priority_seats')->default(5);
            $table->integer('priority_advance_hours')->default(48);
            $table->integer('priority_release_hours')->default(24);
            $table->boolean('auto_release_enabled')->default(true);
            $table->boolean('priority_system_enabled')->default(true);
            $table->json('priority_packages')->nullable(); // Package IDs that grant priority
            $table->timestamps();
        });

        // Insert default settings
        DB::table('priority_booking_settings')->insert([
            'default_priority_seats' => 5,
            'priority_advance_hours' => 48,
            'priority_release_hours' => 24,
            'auto_release_enabled' => true,
            'priority_system_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priority_booking_settings');
    }
};