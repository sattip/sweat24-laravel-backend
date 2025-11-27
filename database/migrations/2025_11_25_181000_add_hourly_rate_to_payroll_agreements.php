<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        DB::statement('CREATE TABLE payroll_agreements_new (
            "id" integer primary key autoincrement not null,
            "instructor_id" integer not null,
            "instructor_name" varchar not null,
            "description" text not null,
            "type" varchar check ("type" in (\'bonus\', \'deduction\', \'special_rate\', \'hourly_rate\')) not null,
            "amount" numeric not null,
            "is_recurring" tinyint(1) not null default \'0\',
            "start_date" date not null,
            "end_date" date,
            "is_active" tinyint(1) not null default \'1\',
            "created_at" datetime,
            "updated_at" datetime,
            foreign key("instructor_id") references "instructors"("id") on delete cascade
        )');

        // Copy data from old table
        DB::statement('INSERT INTO payroll_agreements_new SELECT * FROM payroll_agreements');

        // Drop old table
        DB::statement('DROP TABLE payroll_agreements');

        // Rename new table
        DB::statement('ALTER TABLE payroll_agreements_new RENAME TO payroll_agreements');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - hourly_rate can stay as a valid type
    }
};
