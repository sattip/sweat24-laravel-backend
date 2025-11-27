<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SendScheduledNotifications;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send scheduled notifications every minute
        $schedule->job(new SendScheduledNotifications)
            ->everyMinute()
            ->name('send-scheduled-notifications')
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old notification logs (keep last 30 days)
        $schedule->command('cleanup:notification-logs')
            ->daily()
            ->at('02:00');

        // Clean up sent notifications older than 7 days
        $schedule->command('cleanup:sent-notifications')
            ->daily()
            ->at('02:30');

        // Release priority seats for upcoming classes
        $schedule->command('priority:release-seats')
            ->everyThirtyMinutes()
            ->name('release-priority-seats')
            ->withoutOverlapping()
            ->runInBackground();

        // Process expired waitlist notifications
        $schedule->command('waitlist:process-expired')
            ->everyFiveMinutes()
            ->name('process-expired-waitlist')
            ->withoutOverlapping()
            ->runInBackground();

        // Process churn feedback (send surveys, reminders, and follow-ups)
        $schedule->command('churn:process')
            ->dailyAt('10:00')
            ->name('process-churn-feedback')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

