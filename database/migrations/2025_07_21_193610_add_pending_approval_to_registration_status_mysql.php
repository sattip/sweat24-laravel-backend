<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for MySQL - uses proper Laravel Schema builder
     */
    public function up(): void
    {
        // Change registration_status to string for flexibility
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_status')->default('pending_approval')->change();

            // Only add approved_at if it doesn't exist
            if (!Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }

            // Only add approved_by if it doesn't exist
            if (!Schema::hasColumn('users', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Update existing users
        \DB::table('users')
            ->where('registration_status', 'pending_terms')
            ->where('status', 'inactive')
            ->update(['registration_status' => 'pending_approval']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_at', 'approved_by']);
            $table->string('registration_status')->default('pending_terms')->change();
        });
        
        // Revert any pending_approval back to pending_terms
        \DB::table('users')
            ->where('registration_status', 'pending_approval')
            ->update(['registration_status' => 'pending_terms']);
    }
};