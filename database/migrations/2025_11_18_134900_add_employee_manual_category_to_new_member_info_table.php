<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE new_member_info MODIFY COLUMN category ENUM('general', 'rules', 'benefits', 'schedule', 'equipment', 'faq', 'employee_manual') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE new_member_info MODIFY COLUMN category ENUM('general', 'rules', 'benefits', 'schedule', 'equipment', 'faq') NOT NULL");
    }
};
