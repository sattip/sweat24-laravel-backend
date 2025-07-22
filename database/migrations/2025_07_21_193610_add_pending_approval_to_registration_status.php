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
        // For SQLite, we need to recreate the enum by modifying the table
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Add approval-related fields first
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('registration_completed_at');
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            });
            
            // Add foreign key constraint for approved_by
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
            
            // Update existing users to have pending_approval status if they are pending_terms
            DB::statement("UPDATE users SET registration_status = 'pending_approval' WHERE registration_status = 'pending_terms' AND status = 'inactive'");
            
        } else {
            // For other databases, alter the enum directly
            DB::statement("ALTER TABLE users MODIFY COLUMN registration_status ENUM('pending_approval', 'pending_terms', 'pending_signature', 'completed') DEFAULT 'pending_approval'");
            
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('registration_completed_at');
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_at', 'approved_by']);
        });
        
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Revert any pending_approval back to pending_terms
            DB::statement("UPDATE users SET registration_status = 'pending_terms' WHERE registration_status = 'pending_approval'");
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN registration_status ENUM('pending_terms', 'pending_signature', 'completed') DEFAULT 'pending_terms'");
        }
    }
};
