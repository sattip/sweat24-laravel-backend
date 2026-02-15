<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds the cancellation_policy_id column that the earlier
     * migration (2025_06_26_223000) could not add because the cancellation_policies
     * table did not exist yet at that point.
     */
    public function up(): void
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('gym_classes', 'cancellation_policy_id')) {
                $table->foreignId('cancellation_policy_id')
                      ->nullable()
                      ->constrained('cancellation_policies')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gym_classes', function (Blueprint $table) {
            if (Schema::hasColumn('gym_classes', 'cancellation_policy_id')) {
                $table->dropForeign(['cancellation_policy_id']);
                $table->dropColumn('cancellation_policy_id');
            }
        });
    }
};
