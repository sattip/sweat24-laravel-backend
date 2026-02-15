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
        Schema::create('points_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Unique key for each setting (e.g., 'points_per_euro')
            $table->text('value'); // JSON or text value for the setting
            $table->string('type')->default('string'); // Type: string, number, boolean, json
            $table->text('description')->nullable(); // Human-readable description
            $table->boolean('is_active')->default(true); // Whether this setting is active
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_settings');
    }
};
