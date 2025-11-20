<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassWaitlist;
use App\Models\GymClass;
use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessExpiredWaitlistNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:process-expired
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired waitlist notifications and notify next person in line';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $verbose = $this->getOutput()->isVerbose();

        $this->info('🔍 Checking for expired waitlist notifications...');

        // Find all expired waitlist notifications
        $expiredEntries = ClassWaitlist::where('status', 'notified')
            ->where('expires_at', '<=', now())
            ->with(['user', 'gymClass'])
            ->get();

        if ($expiredEntries->isEmpty()) {
            $this->info('✓ No expired waitlist notifications found.');
            return 0;
        }

        $this->warn("Found {$expiredEntries->count()} expired waitlist notification(s)");

        $processedCount = 0;
        $errorCount = 0;

        foreach ($expiredEntries as $entry) {
            try {
                DB::beginTransaction();

                if ($verbose) {
                    $this->line("Processing: User #{$entry->user_id} ({$entry->user->name}) for class #{$entry->class_id}");
                }

                if (!$dryRun) {
                    // Mark as expired
                    $entry->update(['status' => 'expired']);

                    Log::info('Waitlist notification expired', [
                        'waitlist_id' => $entry->id,
                        'user_id' => $entry->user_id,
                        'class_id' => $entry->class_id,
                        'expired_at' => $entry->expires_at,
                    ]);

                    // Process next person in line if class still has available spots
                    $gymClass = $entry->gymClass;
                    if ($gymClass && $gymClass->hasAvailableSpots()) {
                        $waitlistController = new WaitlistController();
                        $result = $waitlistController->processNextInLine($gymClass);

                        if ($verbose) {
                            $this->line("  → Next person processed for class #{$gymClass->id}");
                        }
                    } else {
                        if ($verbose) {
                            $this->line("  → Class #{$entry->class_id} is still full, no action needed");
                        }
                    }
                }

                DB::commit();
                $processedCount++;

                if ($verbose) {
                    $this->info("  ✓ Processed successfully");
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $errorCount++;

                Log::error('Failed to process expired waitlist notification', [
                    'waitlist_id' => $entry->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->error("  ✗ Error processing entry #{$entry->id}: {$e->getMessage()}");
            }
        }

        // Summary
        $this->newLine();
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes were made");
        }

        $this->info("Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Found', $expiredEntries->count()],
                ['Processed', $processedCount],
                ['Errors', $errorCount],
            ]
        );

        return $errorCount > 0 ? 1 : 0;
    }
}
