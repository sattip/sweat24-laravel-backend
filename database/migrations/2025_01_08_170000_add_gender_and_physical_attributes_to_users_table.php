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
            // Add gender field for proper user profile completion
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->after('date_of_birth');
            
            // Add physical attributes that may be needed for fitness tracking
            // These are optional and should only be filled by user choice, never auto-populated
            $table->decimal('weight', 5, 2)->nullable()->after('gender')->comment('Weight in kg, user-provided only');
            $table->decimal('height', 5, 2)->nullable()->after('weight')->comment('Height in cm, user-provided only');
            
            // Track when profile was last updated to prevent unwanted auto-changes
            $table->timestamp('profile_last_updated')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'weight', 'height', 'profile_last_updated']);
        });
    }
};
