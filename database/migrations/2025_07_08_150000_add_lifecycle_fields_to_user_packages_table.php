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
        Schema::table('user_packages', function (Blueprint $table) {
            // Add lifecycle tracking fields
            $table->boolean('is_frozen')->default(false);
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('unfrozen_at')->nullable();
            $table->integer('freeze_duration_days')->nullable();
            $table->timestamp('last_notification_sent_at')->nullable();
            $table->string('notification_stage')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->foreignId('renewed_from_package_id')->nullable()->constrained('user_packages');
            $table->timestamp('renewed_at')->nullable();
            
            // Update status enum to include more states
            $table->dropColumn('status');
        });
        
        // Re-add status with new enum values
        Schema::table('user_packages', function (Blueprint $table) {
            $table->enum('status', ['active', 'paused', 'expired', 'expiring_soon', 'frozen'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn([
                'is_frozen',
                'frozen_at',
                'unfrozen_at',
                'freeze_duration_days',
                'last_notification_sent_at',
                'notification_stage',
                'auto_renew',
                'renewed_from_package_id',
                'renewed_at'
            ]);
            
            $table->dropColumn('status');
        });
        
        Schema::table('user_packages', function (Blueprint $table) {
            $table->enum('status', ['active', 'paused', 'expired'])->default('active');
        });
    }
};