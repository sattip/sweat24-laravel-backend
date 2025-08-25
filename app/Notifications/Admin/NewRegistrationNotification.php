<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRegistrationNotification extends Notification implements ShouldQueue
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
        $adminUrl = config('app.url') . '/admin/users/' . $this->user->id;

        return (new MailMessage)
            ->subject('New User Registration - Sweat93')
            ->view('emails.admin.new-registration', [
                'user' => $this->user,
                'admin' => $notifiable,
                'adminUrl' => $adminUrl,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Νέα εγγραφή χρήστη',
            'message' => "Ο χρήστης {$this->user->first_name} {$this->user->last_name} ({$this->user->email}) εγγράφηκε στην πλατφόρμα και αναμένει έγκριση.",
            'user_id' => $this->user->id,
            'user_name' => $this->user->first_name . ' ' . $this->user->last_name,
            'user_email' => $this->user->email,
            'user_phone' => $this->user->phone,
            'user_status' => $this->user->status,
            'registration_date' => $this->user->created_at->format('Y-m-d H:i:s'),
            'requires_approval' => $this->user->status === 'pending',
            'action_url' => '/admin/users/' . $this->user->id,
            'icon' => 'user-plus',
            'type' => 'new_registration',
            'priority' => 'normal'
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