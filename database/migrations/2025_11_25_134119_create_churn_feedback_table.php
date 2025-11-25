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
        Schema::create('churn_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_package_id')->constrained('user_packages')->onDelete('cascade');
            $table->date('expired_at');

            // Notification tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Status: churn / pause / renewed
            $table->enum('status', ['pending', 'churn', 'pause', 'renewed'])->default('pending');

            // Survey type
            $table->enum('survey_type', ['quick', 'mini'])->nullable();

            // Reasons (can select multiple via JSON array)
            $table->json('reasons')->nullable();

            // Specific reason flags for analytics
            $table->boolean('reason_will_continue')->default(false);
            $table->boolean('reason_price_value')->default(false);
            $table->boolean('reason_financial_issue')->default(false);
            $table->boolean('reason_schedule')->default(false);
            $table->boolean('reason_program_mismatch')->default(false);
            $table->boolean('reason_trainer_mismatch')->default(false);
            $table->boolean('reason_distance')->default(false);
            $table->boolean('reason_health')->default(false);
            $table->boolean('reason_priorities')->default(false);
            $table->boolean('reason_other')->default(false);

            // Free comment
            $table->text('comment')->nullable();

            // Improvement suggestions (array of selections)
            $table->json('improvements')->nullable();
            $table->text('improvement_comment')->nullable();

            // Return intent (0-10 scale)
            $table->tinyInteger('return_intent_score')->nullable();

            // Future return intent: Yes / Maybe / No
            $table->enum('future_return_intent', ['yes', 'maybe', 'no'])->nullable();

            // Win-back offer
            $table->boolean('wants_alternative_package')->default(false);
            $table->string('winback_offer_type')->nullable();
            $table->boolean('winback_consent')->default(false);
            $table->timestamp('winback_offer_sent_at')->nullable();
            $table->boolean('winback_accepted')->default(false);
            $table->timestamp('winback_accepted_at')->nullable();

            // Pause tracking
            $table->boolean('pause_response')->default(false);
            $table->timestamp('pause_followup_sent_at')->nullable();

            // Opt-out
            $table->boolean('opted_out')->default(false);
            $table->timestamp('opted_out_at')->nullable();

            // No Return comment (if they say "No" to future return)
            $table->text('no_return_comment')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('expired_at');
            $table->index(['user_id', 'expired_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('churn_feedback');
    }
};
