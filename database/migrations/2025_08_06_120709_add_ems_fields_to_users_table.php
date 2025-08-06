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
            $table->boolean('ems_interest')->default(false)->after('medical_history');
            $table->json('ems_contraindications')->nullable()->after('ems_interest');
            $table->boolean('ems_liability_accepted')->nullable()->after('ems_contraindications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ems_interest', 'ems_contraindications', 'ems_liability_accepted']);
        });
    }
};
