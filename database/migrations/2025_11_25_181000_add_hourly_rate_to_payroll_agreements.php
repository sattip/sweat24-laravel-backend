<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payroll_agreements MODIFY COLUMN type ENUM('bonus', 'deduction', 'special_rate', 'hourly_rate') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payroll_agreements MODIFY COLUMN type ENUM('bonus', 'deduction', 'special_rate') NOT NULL");
    }
};
