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
            $table->boolean('is_frozen')->default(false)->after('status');
            $table->timestamp('frozen_at')->nullable()->after('is_frozen');
            $table->timestamp('unfrozen_at')->nullable()->after('frozen_at');
            $table->integer('freeze_duration_days')->nullable()->after('unfrozen_at');
            $table->timestamp('last_notification_sent_at')->nullable()->after('freeze_duration_days');
            $table->string('notification_stage')->nullable()->after('last_notification_sent_at');
            $table->boolean('auto_renew')->default(false)->after('notification_stage');
            $table->foreignId('renewed_from_package_id')->nullable()->constrained('user_packages')->after('auto_renew');
            $table->timestamp('renewed_at')->nullable()->after('renewed_from_package_id');
            
            // Update status enum to include more states
            $table->dropColumn('status');
        });
        
        // Re-add status with new enum values
        Schema::table('user_packages', function (Blueprint $table) {
            $table->enum('status', ['active', 'paused', 'expired', 'expiring_soon', 'frozen'])->default('active')->after('total_sessions');
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
            $table->enum('status', ['active', 'paused', 'expired'])->default('active')->after('total_sessions');
        });
    }
};