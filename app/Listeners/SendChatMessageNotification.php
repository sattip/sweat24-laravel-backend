<?php

namespace App\Listeners;

use App\Events\ChatMessageReceived;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendChatMessageNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(ChatMessageReceived $event): void
    {
        try {
            $message = $event->message;
            $recipient = $event->recipient;
            $isForUser = $event->isForUser;

            // Prepare notification content based on sender type
            if ($isForUser) {
                // Message from admin to user
                $title = 'Νέο μήνυμα από το SWEAT24! 💬';
                $messageText = "Λάβατε νέο μήνυμα από την ομάδα μας. Δείτε τι σας γράψαμε!";
            } else {
                // Message from user to admin
                $title = 'Νέο μήνυμα από πελάτη 📨';
                $messageText = "Ο {$message->sender->name} σας έστειλε νέο μήνυμα στο chat.";
            }

            // Create push notification
            $notification = $this->notificationService->createNotification([
                'title' => $title,
                'message' => $messageText,
                'type' => 'chat_message',
                'priority' => 'medium',
                'channels' => ['in_app', 'push'],
                'filters' => [
                    'inline' => [
                        'user_ids' => [$recipient->id],
                    ],
                ],
                'status' => 'sent',
            ]);

            Log::info('Chat message notification sent', [
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'sender_id' => $message->sender_id,
                'is_for_user' => $isForUser,
                'notification_id' => $notification->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send chat message notification', [
                'message_id' => $event->message->id,
                'recipient_id' => $event->recipient->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ChatMessageReceived $event, $exception): void
    {
        Log::error('Chat message notification job failed', [
            'message_id' => $event->message->id,
            'recipient_id' => $event->recipient->id,
            'exception' => $exception->getMessage(),
        ]);
    }
} 