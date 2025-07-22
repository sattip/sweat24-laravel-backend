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
        // Add pending_approval to the status enum
        if (DB::connection()->getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            Schema::table('users', function (Blueprint $table) {
                $table->string('status_temp')->default('pending_approval')->after('status');
            });
            
            // Copy data
            DB::statement("UPDATE users SET status_temp = CASE 
                WHEN status = 'active' THEN 'active'
                WHEN status = 'inactive' THEN 'pending_approval'
                WHEN status = 'expired' THEN 'expired'
                ELSE 'pending_approval'
            END");
            
            // Drop old column and rename new one
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('status_temp', 'status');
            });
        } else {
            // For MySQL
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'expired', 'pending_approval') DEFAULT 'pending_approval'");
        }

        \Log::info('🔧 Added pending_approval to user status enum', [
            'driver' => DB::connection()->getDriverName(),
            'timestamp' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert users with pending_approval to inactive
        DB::table('users')
            ->where('status', 'pending_approval')
            ->update(['status' => 'inactive']);

        if (DB::connection()->getDriverName() === 'sqlite') {
            // For SQLite, recreate without pending_approval
            Schema::table('users', function (Blueprint $table) {
                $table->string('status_temp')->default('inactive')->after('status');
            });
            
            DB::statement("UPDATE users SET status_temp = CASE 
                WHEN status = 'active' THEN 'active'
                WHEN status = 'expired' THEN 'expired'
                ELSE 'inactive'
            END");
            
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('status_temp', 'status');
            });
        } else {
            // For MySQL
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'expired') DEFAULT 'inactive'");
        }
    }
}; 