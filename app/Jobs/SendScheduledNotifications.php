<?php

namespace App\Jobs;

use App\Models\ScheduledNotification;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PushNotificationService $pushService): void
    {
        $notifications = ScheduledNotification::getPendingNotifications();

        Log::info('Processing scheduled notifications', [
            'count' => $notifications->count()
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                // Add notification ID to data for tracking
                $data = array_merge($notification->data ?? [], [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                    'related_id' => $notification->related_id
                ]);

                $results = $pushService->sendToUser(
                    $notification->user_id,
                    $notification->title,
                    $notification->body,
                    $data
                );

                // Check if at least one notification was sent successfully
                $success = collect($results)->contains('success', true);

                if ($success) {
                    $notification->markAsSent();
                    $sent++;
                    
                    Log::info('Scheduled notification sent', [
                        'notification_id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'type' => $notification->type,
                        'devices_reached' => collect($results)->where('success', true)->count()
                    ]);
                } else {
                    $failed++;
                    Log::warning('Failed to send scheduled notification to any device', [
                        'notification_id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'type' => $notification->type,
                        'results' => $results
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Exception while sending scheduled notification', [
                    'notification_id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        if ($notifications->count() > 0) {
            Log::info('Scheduled notifications job completed', [
                'total' => $notifications->count(),
                'sent' => $sent,
                'failed' => $failed
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendScheduledNotifications job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}