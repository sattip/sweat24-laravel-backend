<?php

namespace App\Console\Commands;

use App\Models\ChurnFeedback;
use App\Models\UserPackage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessChurnFeedback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'churn:process
                            {--dry-run : Run without actually sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process churn feedback: detect expired packages, send surveys, and manage follow-ups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode...');
        }

        // Step 1: Detect newly expired packages (5 days ago)
        $this->detectExpiredPackages($dryRun);

        // Step 2: Send reminders (10 days after first message)
        $this->sendReminders($dryRun);

        // Step 3: Send pause follow-ups (15 days after pause response)
        $this->sendPauseFollowups($dryRun);

        // Step 4: Check for renewals and update status
        $this->checkForRenewals($dryRun);

        // Step 5: Auto-delete old data (GDPR - 12 months)
        $this->cleanupOldData($dryRun);

        $this->info('Churn feedback processing completed.');

        return Command::SUCCESS;
    }

    /**
     * Detect packages that expired 5 days ago and create feedback entries.
     */
    private function detectExpiredPackages(bool $dryRun): void
    {
        $this->info('Detecting expired packages (5 days ago)...');

        $fiveDaysAgo = now()->subDays(5)->toDateString();

        // Find expired packages
        $expiredPackages = UserPackage::where('status', UserPackage::STATUS_EXPIRED)
            ->whereDate('expiry_date', $fiveDaysAgo)
            ->where('is_frozen', false)
            ->whereDoesntHave('renewals') // Not renewed
            ->with('user')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($expiredPackages as $package) {
            $user = $package->user;

            if (!$user) {
                $skipped++;
                continue;
            }

            // Skip if user is frozen/paused
            if ($user->status === 'frozen' || $user->status === 'paused') {
                $skipped++;
                continue;
            }

            // Skip if user has recent survey (within 60 days)
            if (ChurnFeedback::hasRecentSurvey($user->id, 60)) {
                $skipped++;
                continue;
            }

            // Skip if user has opted out
            $hasOptedOut = ChurnFeedback::where('user_id', $user->id)
                ->where('opted_out', true)
                ->exists();

            if ($hasOptedOut) {
                $skipped++;
                continue;
            }

            // Check if user has renewed with another package
            $hasActivePackage = UserPackage::where('user_id', $user->id)
                ->where('status', UserPackage::STATUS_ACTIVE)
                ->exists();

            if ($hasActivePackage) {
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                // Create churn feedback entry
                $feedback = ChurnFeedback::create([
                    'user_id' => $user->id,
                    'user_package_id' => $package->id,
                    'expired_at' => $package->expiry_date,
                    'sent_at' => now(),
                    'status' => ChurnFeedback::STATUS_PENDING,
                ]);

                // TODO: Send push notification to mobile app
                // This would integrate with your notification system
                $this->sendChurnSurveyNotification($user, $feedback, 'first');

                $created++;
            } else {
                $this->line("Would create feedback for user: {$user->name} (ID: {$user->id})");
                $created++;
            }
        }

        $this->info("Created: {$created}, Skipped: {$skipped}");
    }

    /**
     * Send reminders for unresponded surveys (10 days after first message).
     */
    private function sendReminders(bool $dryRun): void
    {
        $this->info('Sending reminders (10 days after first message)...');

        $fiveDaysAgo = now()->subDays(5);

        $needingReminder = ChurnFeedback::needingReminder()
            ->where('sent_at', '<=', $fiveDaysAgo)
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($needingReminder as $feedback) {
            if (!$feedback->user) {
                continue;
            }

            // Check if user has renewed since
            $hasActivePackage = UserPackage::where('user_id', $feedback->user_id)
                ->where('status', UserPackage::STATUS_ACTIVE)
                ->exists();

            if ($hasActivePackage) {
                $feedback->status = ChurnFeedback::STATUS_RENEWED;
                $feedback->save();
                continue;
            }

            if (!$dryRun) {
                $feedback->reminder_sent_at = now();
                $feedback->save();

                $this->sendChurnSurveyNotification($feedback->user, $feedback, 'reminder');
                $sent++;
            } else {
                $this->line("Would send reminder to: {$feedback->user->name} (ID: {$feedback->user_id})");
                $sent++;
            }
        }

        $this->info("Reminders sent: {$sent}");
    }

    /**
     * Send follow-ups for users who said they'll continue (15 days after pause response).
     */
    private function sendPauseFollowups(bool $dryRun): void
    {
        $this->info('Sending pause follow-ups (15 days after pause response)...');

        $fifteenDaysAgo = now()->subDays(15);

        $needingFollowup = ChurnFeedback::needingPauseFollowup()
            ->where('responded_at', '<=', $fifteenDaysAgo)
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($needingFollowup as $feedback) {
            if (!$feedback->user) {
                continue;
            }

            // Check if user has renewed since
            $hasActivePackage = UserPackage::where('user_id', $feedback->user_id)
                ->where('status', UserPackage::STATUS_ACTIVE)
                ->exists();

            if ($hasActivePackage) {
                $feedback->status = ChurnFeedback::STATUS_RENEWED;
                $feedback->save();
                continue;
            }

            if (!$dryRun) {
                $feedback->pause_followup_sent_at = now();
                $feedback->save();

                $this->sendPauseFollowupNotification($feedback->user, $feedback);
                $sent++;
            } else {
                $this->line("Would send pause follow-up to: {$feedback->user->name} (ID: {$feedback->user_id})");
                $sent++;
            }
        }

        $this->info("Pause follow-ups sent: {$sent}");
    }

    /**
     * Check for renewals and update feedback status.
     */
    private function checkForRenewals(bool $dryRun): void
    {
        $this->info('Checking for renewals...');

        $pendingFeedback = ChurnFeedback::whereIn('status', [
            ChurnFeedback::STATUS_PENDING,
            ChurnFeedback::STATUS_PAUSE
        ])->get();

        $updated = 0;

        foreach ($pendingFeedback as $feedback) {
            $hasActivePackage = UserPackage::where('user_id', $feedback->user_id)
                ->where('status', UserPackage::STATUS_ACTIVE)
                ->exists();

            if ($hasActivePackage) {
                if (!$dryRun) {
                    $feedback->status = ChurnFeedback::STATUS_RENEWED;
                    $feedback->save();
                }
                $updated++;
            }
        }

        $this->info("Marked as renewed: {$updated}");
    }

    /**
     * Clean up old data (GDPR - 12 months).
     */
    private function cleanupOldData(bool $dryRun): void
    {
        $this->info('Cleaning up old data (GDPR - 12 months)...');

        $twelveMonthsAgo = now()->subMonths(12);

        $oldRecords = ChurnFeedback::where('created_at', '<', $twelveMonthsAgo);

        $count = $oldRecords->count();

        if (!$dryRun && $count > 0) {
            $oldRecords->delete();
        }

        $this->info("Deleted old records: {$count}");
    }

    /**
     * Send churn survey notification (placeholder for actual implementation).
     */
    private function sendChurnSurveyNotification(User $user, ChurnFeedback $feedback, string $type): void
    {
        $firstName = explode(' ', $user->name)[0] ?? $user->name;

        if ($type === 'first') {
            $message = "Γεια σου {$firstName}! Είδαμε ότι το πακέτο σου έληξε πριν λίγες μέρες 😊 Θα μας πεις με 1 κλικ γιατί σταμάτησες ή αν σκοπεύεις να συνεχίσεις; Θα μας βοηθήσει πολύ 🙏";
        } else {
            $message = "Αν έχεις 20'' να μας πεις γιατί σταμάτησες ή αν σχεδιάζεις να επιστρέψεις, θα εκτιμούσαμε πολύ τη γνώμη σου 💬";
        }

        // Log the notification (actual push notification implementation depends on your system)
        Log::info("Churn survey notification ({$type})", [
            'user_id' => $user->id,
            'feedback_id' => $feedback->id,
            'message' => $message
        ]);

        // TODO: Integrate with your push notification service (e.g., Firebase, OneSignal)
        // Example: PushNotification::send($user, 'churn_survey', ['feedback_id' => $feedback->id, 'message' => $message]);
    }

    /**
     * Send pause follow-up notification.
     */
    private function sendPauseFollowupNotification(User $user, ChurnFeedback $feedback): void
    {
        $firstName = explode(' ', $user->name)[0] ?? $user->name;
        $message = "Γεια σου {$firstName}! Να σε βοηθήσουμε να ξεκινήσεις ξανά; 💪";

        Log::info("Pause follow-up notification", [
            'user_id' => $user->id,
            'feedback_id' => $feedback->id,
            'message' => $message
        ]);

        // TODO: Integrate with your push notification service
    }
}
