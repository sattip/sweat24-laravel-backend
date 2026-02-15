<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:cleanup {--days=90 : Number of days to keep activity logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old activity logs older than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up activity logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");
        
        $count = ActivityLog::where('created_at', '<', $cutoffDate)->count();
        
        if ($count === 0) {
            $this->info('No old activity logs found to clean up.');
            return;
        }
        
        $this->info("Found {$count} activity logs to delete.");
        
        if ($this->confirm('Do you want to proceed with deletion?')) {
            $deleted = ActivityLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("Successfully deleted {$deleted} activity logs.");
        } else {
            $this->info('Cleanup cancelled.');
        }
    }
}
