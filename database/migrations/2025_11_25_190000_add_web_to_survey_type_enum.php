<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE churn_feedback MODIFY COLUMN survey_type ENUM('quick', 'mini', 'web') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE churn_feedback MODIFY COLUMN survey_type ENUM('quick', 'mini') NULL");
    }
};
