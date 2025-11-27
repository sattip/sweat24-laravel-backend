<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SQLite doesn't support ALTER COLUMN, so we need to recreate the constraint
     */
    public function up(): void
    {
        // For SQLite, we need to remove the CHECK constraint
        // Since SQLite doesn't support modifying constraints directly,
        // we'll update the column to allow any value (effectively removing the enum constraint)

        // First, let's update any existing constraint by recreating the table structure
        // For now, we'll just allow the web value by using a raw query to update the check constraint

        // SQLite workaround: drop and recreate the column constraints
        DB::statement('PRAGMA foreign_keys=off');

        // Create new table without the constraint
        DB::statement('
            CREATE TABLE churn_feedback_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                user_package_id INTEGER,
                expired_at DATE NOT NULL,
                sent_at DATETIME,
                reminder_sent_at DATETIME,
                responded_at DATETIME,
                status VARCHAR NOT NULL DEFAULT "pending" CHECK(status IN ("pending", "churn", "pause", "renewed")),
                survey_type VARCHAR CHECK(survey_type IN ("quick", "mini", "web")),
                trigger_source VARCHAR,
                reasons TEXT,
                reason_will_continue TINYINT(1) NOT NULL DEFAULT 0,
                reason_price_value TINYINT(1) NOT NULL DEFAULT 0,
                reason_financial_issue TINYINT(1) NOT NULL DEFAULT 0,
                reason_schedule TINYINT(1) NOT NULL DEFAULT 0,
                reason_program_mismatch TINYINT(1) NOT NULL DEFAULT 0,
                reason_trainer_mismatch TINYINT(1) NOT NULL DEFAULT 0,
                reason_distance TINYINT(1) NOT NULL DEFAULT 0,
                reason_health TINYINT(1) NOT NULL DEFAULT 0,
                reason_priorities TINYINT(1) NOT NULL DEFAULT 0,
                reason_other TINYINT(1) NOT NULL DEFAULT 0,
                comment TEXT,
                improvements TEXT,
                improvement_comment TEXT,
                return_intent_score TINYINT,
                future_return_intent VARCHAR CHECK(future_return_intent IN ("yes", "maybe", "no")),
                wants_alternative_package TINYINT(1) NOT NULL DEFAULT 0,
                winback_offer_type VARCHAR,
                winback_consent TINYINT(1) NOT NULL DEFAULT 0,
                winback_offer_sent_at DATETIME,
                winback_accepted TINYINT(1) NOT NULL DEFAULT 0,
                winback_accepted_at DATETIME,
                pause_response TINYINT(1) NOT NULL DEFAULT 0,
                pause_followup_sent_at DATETIME,
                opted_out TINYINT(1) NOT NULL DEFAULT 0,
                opted_out_at DATETIME,
                no_return_comment TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');

        // Copy data
        DB::statement('
            INSERT INTO churn_feedback_new
            SELECT * FROM churn_feedback
        ');

        // Drop old table
        DB::statement('DROP TABLE churn_feedback');

        // Rename new table
        DB::statement('ALTER TABLE churn_feedback_new RENAME TO churn_feedback');

        // Recreate indexes
        DB::statement('CREATE INDEX churn_feedback_status_index ON churn_feedback(status)');
        DB::statement('CREATE INDEX churn_feedback_expired_at_index ON churn_feedback(expired_at)');
        DB::statement('CREATE INDEX churn_feedback_user_id_expired_at_index ON churn_feedback(user_id, expired_at)');

        DB::statement('PRAGMA foreign_keys=on');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversing this migration
    }
};
