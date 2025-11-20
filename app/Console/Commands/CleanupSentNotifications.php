<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledNotification;

class CleanupSentNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:sent-notifications {--days=7 : Number of days to keep sent notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old sent notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up sent notifications older than {$days} days...");

        $deleted = ScheduledNotification::where('is_sent', true)
            ->where('sent_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deleted} old sent notifications.");

        return Command::SUCCESS;
    }
}