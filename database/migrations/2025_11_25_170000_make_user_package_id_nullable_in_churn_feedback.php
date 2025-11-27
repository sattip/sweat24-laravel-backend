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
        // Create a new table with user_package_id nullable
        DB::statement('CREATE TABLE churn_feedback_new (
            "id" integer primary key autoincrement not null,
            "user_id" integer not null,
            "user_package_id" integer,
            "expired_at" date not null,
            "sent_at" datetime,
            "reminder_sent_at" datetime,
            "responded_at" datetime,
            "status" varchar check ("status" in (\'pending\', \'churn\', \'pause\', \'renewed\')) not null default \'pending\',
            "survey_type" varchar check ("survey_type" in (\'quick\', \'mini\')),
            "trigger_source" varchar,
            "reasons" text,
            "reason_will_continue" tinyint(1) not null default \'0\',
            "reason_price_value" tinyint(1) not null default \'0\',
            "reason_financial_issue" tinyint(1) not null default \'0\',
            "reason_schedule" tinyint(1) not null default \'0\',
            "reason_program_mismatch" tinyint(1) not null default \'0\',
            "reason_trainer_mismatch" tinyint(1) not null default \'0\',
            "reason_distance" tinyint(1) not null default \'0\',
            "reason_health" tinyint(1) not null default \'0\',
            "reason_priorities" tinyint(1) not null default \'0\',
            "reason_other" tinyint(1) not null default \'0\',
            "comment" text,
            "improvements" text,
            "improvement_comment" text,
            "return_intent_score" tinyint,
            "future_return_intent" varchar check ("future_return_intent" in (\'yes\', \'maybe\', \'no\')),
            "wants_alternative_package" tinyint(1) not null default \'0\',
            "winback_offer_type" varchar,
            "winback_consent" tinyint(1) not null default \'0\',
            "winback_offer_sent_at" datetime,
            "winback_accepted" tinyint(1) not null default \'0\',
            "winback_accepted_at" datetime,
            "pause_response" tinyint(1) not null default \'0\',
            "pause_followup_sent_at" datetime,
            "opted_out" tinyint(1) not null default \'0\',
            "opted_out_at" datetime,
            "no_return_comment" text,
            "created_at" datetime,
            "updated_at" datetime,
            foreign key("user_id") references "users"("id") on delete cascade,
            foreign key("user_package_id") references "user_packages"("id") on delete cascade
        )');

        // Copy data from old table
        DB::statement('INSERT INTO churn_feedback_new SELECT * FROM churn_feedback');

        // Drop old table
        DB::statement('DROP TABLE churn_feedback');

        // Rename new table
        DB::statement('ALTER TABLE churn_feedback_new RENAME TO churn_feedback');

        // Recreate indexes
        DB::statement('CREATE INDEX "churn_feedback_status_index" on "churn_feedback" ("status")');
        DB::statement('CREATE INDEX "churn_feedback_expired_at_index" on "churn_feedback" ("expired_at")');
        DB::statement('CREATE INDEX "churn_feedback_user_id_expired_at_index" on "churn_feedback" ("user_id", "expired_at")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - user_package_id can stay nullable
    }
};
