<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
            ->subject('Welcome to Sweat93 - Registration Confirmation')
            ->view('emails.auth.registration-confirmation', [
                'user' => $this->user,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Καλώς ήρθατε στο Sweat93!',
            'message' => 'Η εγγραφή σας έγινε με επιτυχία. Ο λογαριασμός σας θα εγκριθεί εντός 24 ωρών.',
            'user_id' => $this->user->id,
            'user_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'user_email' => $this->user->email,
            'action_url' => '/profile',
            'icon' => 'user-plus',
            'type' => 'registration_confirmation'
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