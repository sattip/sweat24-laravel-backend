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
        Schema::table('fitness_classes', function (Blueprint $table) {
            $table->integer('priority_seats')->default(0); // Number of seats reserved for priority booking users
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fitness_classes', function (Blueprint $table) {
            $table->dropColumn('priority_seats');
        });
    }
};
