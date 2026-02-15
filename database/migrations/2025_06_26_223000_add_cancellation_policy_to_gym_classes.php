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
        if (!Schema::hasTable('gym_classes') || !Schema::hasTable('cancellation_policies')) {
            return;
        }

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
            $table->dropForeign(['cancellation_policy_id']);
            $table->dropColumn('cancellation_policy_id');
        });
    }
};

