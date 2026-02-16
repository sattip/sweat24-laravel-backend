<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
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
        $resetUrl = config('app.url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email);

        return (new MailMessage)
            ->subject('Password Reset Request - Sweat93')
            ->view('emails.auth.password-reset', [
                'user' => $this->user,
                'token' => $this->token,
                'resetUrl' => $resetUrl,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Αίτημα επαναφοράς κωδικού πρόσβασης',
            'message' => 'Λάβαμε ένα αίτημα επαναφοράς κωδικού πρόσβασης για τον λογαριασμό σας. Ελέγξτε το email σας για οδηγίες.',
            'user_id' => $this->user->id,
            'user_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'user_email' => $this->user->email,
            'token' => $this->token,
            'action_url' => '/reset-password?token=' . $this->token,
            'icon' => 'key',
            'type' => 'password_reset'
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