<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to change the nullable constraint
        // First add the trigger_source column
        Schema::table('churn_feedback', function (Blueprint $table) {
            $table->string('trigger_source')->nullable()->after('survey_type');
        });

        // SQLite doesn't support modifying columns, so we just add the column
        // The user_package_id will be handled by removing the constraint in new inserts
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churn_feedback', function (Blueprint $table) {
            $table->dropColumn('trigger_source');
        });
    }
};
