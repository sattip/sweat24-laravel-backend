<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, string $reason = null)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // TEMP: disabled database
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Registration Update - Sweat93')
            ->view('emails.auth.account-rejected', [
                'user' => $this->user,
                'reason' => $this->reason,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Ενημέρωση εγγραφής λογαριασμού',
            'message' => 'Η αίτηση εγγραφής σας δεν μπόρεσε να εγκριθεί αυτή τη στιγμή. Παρακαλούμε επικοινωνήστε μαζί μας για περισσότερες πληροφορίες.',
            'user_id' => $this->user->id,
            'user_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'user_email' => $this->user->email,
            'reason' => $this->reason,
            'action_url' => '/contact',
            'icon' => 'x-circle',
            'type' => 'account_rejected'
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}