<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurnFeedback extends Model
{
    use HasFactory;

    protected $table = 'churn_feedback';

    protected $fillable = [
        'user_id',
        'user_package_id',
        'expired_at',
        'sent_at',
        'reminder_sent_at',
        'responded_at',
        'status',
        'survey_type',
        'reasons',
        'reason_will_continue',
        'reason_price_value',
        'reason_financial_issue',
        'reason_schedule',
        'reason_program_mismatch',
        'reason_trainer_mismatch',
        'reason_distance',
        'reason_health',
        'reason_priorities',
        'reason_other',
        'comment',
        'improvements',
        'improvement_comment',
        'return_intent_score',
        'future_return_intent',
        'wants_alternative_package',
        'winback_offer_type',
        'winback_consent',
        'winback_offer_sent_at',
        'winback_accepted',
        'winback_accepted_at',
        'pause_response',
        'pause_followup_sent_at',
        'opted_out',
        'opted_out_at',
        'no_return_comment',
    ];

    protected $casts = [
        'expired_at' => 'date',
        'sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'reasons' => 'array',
        'improvements' => 'array',
        'reason_will_continue' => 'boolean',
        'reason_price_value' => 'boolean',
        'reason_financial_issue' => 'boolean',
        'reason_schedule' => 'boolean',
        'reason_program_mismatch' => 'boolean',
        'reason_trainer_mismatch' => 'boolean',
        'reason_distance' => 'boolean',
        'reason_health' => 'boolean',
        'reason_priorities' => 'boolean',
        'reason_other' => 'boolean',
        'wants_alternative_package' => 'boolean',
        'winback_consent' => 'boolean',
        'winback_offer_sent_at' => 'datetime',
        'winback_accepted' => 'boolean',
        'winback_accepted_at' => 'datetime',
        'pause_response' => 'boolean',
        'pause_followup_sent_at' => 'datetime',
        'opted_out' => 'boolean',
        'opted_out_at' => 'datetime',
    ];

    // Constants for status
    const STATUS_PENDING = 'pending';
    const STATUS_CHURN = 'churn';
    const STATUS_PAUSE = 'pause';
    const STATUS_RENEWED = 'renewed';

    // Constants for future return intent
    const RETURN_YES = 'yes';
    const RETURN_MAYBE = 'maybe';
    const RETURN_NO = 'no';

    // Constants for reasons
    const REASON_WILL_CONTINUE = 'will_continue';
    const REASON_PRICE_VALUE = 'price_value';
    const REASON_FINANCIAL_ISSUE = 'financial_issue';
    const REASON_SCHEDULE = 'schedule';
    const REASON_PROGRAM_MISMATCH = 'program_mismatch';
    const REASON_TRAINER_MISMATCH = 'trainer_mismatch';
    const REASON_DISTANCE = 'distance';
    const REASON_HEALTH = 'health';
    const REASON_PRIORITIES = 'priorities';
    const REASON_OTHER = 'other';

    // Win-back offer types
    const WINBACK_HAPPY_HOUR = 'happy_hour';
    const WINBACK_DISCOUNT_10 = 'discount_10';
    const WINBACK_LIGHT_PACK = 'light_pack';
    const WINBACK_RECOVERY_PACK = 'recovery_pack';

    /**
     * Get the user that owns this feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user package associated with this feedback.
     */
    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class);
    }

    /**
     * Check if user has responded to the survey.
     */
    public function hasResponded(): bool
    {
        return $this->responded_at !== null;
    }

    /**
     * Check if reminder should be sent.
     */
    public function shouldSendReminder(): bool
    {
        return $this->sent_at !== null
            && $this->reminder_sent_at === null
            && $this->responded_at === null
            && !$this->opted_out;
    }

    /**
     * Check if pause follow-up should be sent.
     */
    public function shouldSendPauseFollowup(): bool
    {
        return $this->status === self::STATUS_PAUSE
            && $this->pause_response
            && $this->pause_followup_sent_at === null;
    }

    /**
     * Determine win-back offer type based on reason.
     */
    public function determineWinbackOffer(): ?string
    {
        if ($this->reason_schedule) {
            return self::WINBACK_HAPPY_HOUR;
        }
        if ($this->reason_price_value) {
            return self::WINBACK_DISCOUNT_10;
        }
        if ($this->reason_financial_issue) {
            return self::WINBACK_LIGHT_PACK;
        }
        if ($this->reason_health) {
            return self::WINBACK_RECOVERY_PACK;
        }
        return null;
    }

    /**
     * Set reason flags from array of reason codes.
     */
    public function setReasonFlags(array $reasons): void
    {
        $this->reason_will_continue = in_array(self::REASON_WILL_CONTINUE, $reasons);
        $this->reason_price_value = in_array(self::REASON_PRICE_VALUE, $reasons);
        $this->reason_financial_issue = in_array(self::REASON_FINANCIAL_ISSUE, $reasons);
        $this->reason_schedule = in_array(self::REASON_SCHEDULE, $reasons);
        $this->reason_program_mismatch = in_array(self::REASON_PROGRAM_MISMATCH, $reasons);
        $this->reason_trainer_mismatch = in_array(self::REASON_TRAINER_MISMATCH, $reasons);
        $this->reason_distance = in_array(self::REASON_DISTANCE, $reasons);
        $this->reason_health = in_array(self::REASON_HEALTH, $reasons);
        $this->reason_priorities = in_array(self::REASON_PRIORITIES, $reasons);
        $this->reason_other = in_array(self::REASON_OTHER, $reasons);

        // If "will continue" is selected, mark as pause
        if ($this->reason_will_continue) {
            $this->status = self::STATUS_PAUSE;
            $this->pause_response = true;
        } else {
            $this->status = self::STATUS_CHURN;
        }
    }

    /**
     * Scope for pending feedback.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for churned users.
     */
    public function scopeChurned($query)
    {
        return $query->where('status', self::STATUS_CHURN);
    }

    /**
     * Scope for paused users.
     */
    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSE);
    }

    /**
     * Scope for renewed users.
     */
    public function scopeRenewed($query)
    {
        return $query->where('status', self::STATUS_RENEWED);
    }

    /**
     * Scope for users who responded.
     */
    public function scopeResponded($query)
    {
        return $query->whereNotNull('responded_at');
    }

    /**
     * Scope for users needing reminder.
     */
    public function scopeNeedingReminder($query)
    {
        return $query->whereNotNull('sent_at')
            ->whereNull('reminder_sent_at')
            ->whereNull('responded_at')
            ->where('opted_out', false);
    }

    /**
     * Scope for pause users needing follow-up.
     */
    public function scopeNeedingPauseFollowup($query)
    {
        return $query->where('status', self::STATUS_PAUSE)
            ->where('pause_response', true)
            ->whereNull('pause_followup_sent_at');
    }

    /**
     * Check if user has recent survey (within 60 days).
     */
    public static function hasRecentSurvey(int $userId, int $days = 60): bool
    {
        return static::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }

    /**
     * Get reason labels in Greek.
     */
    public static function getReasonLabels(): array
    {
        return [
            self::REASON_WILL_CONTINUE => 'Θα συνεχίσω, απλά έκανα ένα μικρό διάλειμμα',
            self::REASON_PRICE_VALUE => 'Τιμή / Δεν άξιζε για μένα',
            self::REASON_FINANCIAL_ISSUE => 'Οικονομικό θέμα / Προσωρινή δυσκολία',
            self::REASON_SCHEDULE => 'Ωράρια / Διαθεσιμότητα',
            self::REASON_PROGRAM_MISMATCH => 'Πρόγραμμα δεν μου ταίριαξε',
            self::REASON_TRAINER_MISMATCH => 'Προπονητής δεν μου ταίριαξε',
            self::REASON_DISTANCE => 'Απόσταση / Μετακίνηση',
            self::REASON_HEALTH => 'Υγεία / Τραυματισμός',
            self::REASON_PRIORITIES => 'Άλλες προτεραιότητες / χρόνος',
            self::REASON_OTHER => 'Άλλο',
        ];
    }

    /**
     * Get winback offer labels in Greek.
     */
    public static function getWinbackOfferLabels(): array
    {
        return [
            self::WINBACK_HAPPY_HOUR => 'Happy Hour / Ευέλικτο πρόγραμμα',
            self::WINBACK_DISCOUNT_10 => 'Κουπόνι -10%',
            self::WINBACK_LIGHT_PACK => 'Light Pack / Μικρότερη διάρκεια',
            self::WINBACK_RECOVERY_PACK => 'Recovery / Mobility Pack',
        ];
    }
}
