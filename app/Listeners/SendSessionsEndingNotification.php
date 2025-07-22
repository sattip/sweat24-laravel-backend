<?php

namespace App\Listeners;

use App\Events\UserNearSessionsEnd;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSessionsEndingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(UserNearSessionsEnd $event): void
    {
        try {
            $user = $event->user;
            $userPackage = $event->userPackage;
            $remainingSessions = $event->remainingSessions;
            $isLastSession = $event->isLastSession;

            // Prepare notification content based on sessions left
            if ($isLastSession) {
                $title = 'Τελευταία Συνεδρία! ⚠️';
                $message = "Αυτή είναι η τελευταία συνεδρία από το πακέτο σας '{$userPackage->name}'. Ανανεώστε τώρα για να συνεχίσετε!";
                $priority = 'high';
            } else {
                $title = 'Προτελευταία Συνεδρία! 📅';
                $message = "Έχετε μόνο {$remainingSessions} συνεδρίες από το πακέτο σας '{$userPackage->name}'. Σκεφτείτε την ανανέωση!";
                $priority = 'medium';
            }

            // Create push notification
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $message,
                'type' => 'sessions_ending',
                'priority' => $priority,
                'channels' => ['in_app', 'push'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$user->id],
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('Sessions ending notification sent', [
                'user_id' => $user->id,
                'package_id' => $userPackage->id,
                'remaining_sessions' => $remainingSessions,
                'is_last_session' => $isLastSession,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send sessions ending notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserNearSessionsEnd $event, $exception): void
    {
        Log::error('Sessions ending notification job failed', [
            'user_id' => $event->user->id,
            'exception' => $exception->getMessage(),
        ]);
    }
} 