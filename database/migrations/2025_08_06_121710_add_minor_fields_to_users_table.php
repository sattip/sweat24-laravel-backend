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
            $table->boolean('is_minor')->default(false)->after('date_of_birth');
            $table->integer('age_at_registration')->nullable()->after('is_minor');
            $table->index('is_minor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_minor']);
            $table->dropColumn(['is_minor', 'age_at_registration']);
        });
    }
};
