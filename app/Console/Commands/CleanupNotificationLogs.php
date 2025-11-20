<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationLog;
use App\Models\ScheduledNotification;

class CleanupNotificationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:notification-logs {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notification logs and sent notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up notification logs older than {$days} days...");

        // Clean up old notification logs
        $logsDeleted = NotificationLog::where('sent_at', '<', $cutoffDate)->delete();
        $this->info("Deleted {$logsDeleted} old notification logs.");

        // Clean up sent notifications older than 7 days (keep for reference)
        $notificationsDeleted = ScheduledNotification::where('is_sent', true)
            ->where('sent_at', '<', now()->subDays(7))
            ->delete();
        $this->info("Deleted {$notificationsDeleted} old sent notifications.");

        $this->info('Cleanup completed successfully!');

        return Command::SUCCESS;
    }
}