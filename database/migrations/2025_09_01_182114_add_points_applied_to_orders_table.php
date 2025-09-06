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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('points_applied')->default(false)->after('completed_at');
            $table->decimal('points_awarded', 8, 2)->nullable()->after('points_applied');
            $table->timestamp('points_applied_at')->nullable()->after('points_awarded');
            
            $table->index('points_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['points_applied']);
            $table->dropColumn(['points_applied', 'points_awarded', 'points_applied_at']);
        });
    }
};
