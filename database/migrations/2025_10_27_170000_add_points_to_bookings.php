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
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('points_used')->default(0)->after('status');
            $table->decimal('cash_paid', 10, 2)->default(0)->after('points_used');
            $table->decimal('total_cost', 10, 2)->nullable()->after('cash_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['points_used', 'cash_paid', 'total_cost']);
        });
    }
};
