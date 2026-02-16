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
            $table->string('referral_phone', 20)->nullable()->comment('Κινητό συστήσαντος');
            $table->integer('referral_points')->default(0)->comment('Πόντοι από συστάσεις');
            
            // Add index for better performance
            $table->index('referral_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referral_phone']);
            $table->dropColumn(['referral_phone', 'referral_points']);
        });
    }
};