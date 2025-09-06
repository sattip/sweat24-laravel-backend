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
        Schema::table('gym_classes', function (Blueprint $table) {
            $table->foreignId('cancellation_policy_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('cancellation_policies')
                  ->onDelete('set null');
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

