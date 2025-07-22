<?php

namespace App\Notifications;

use App\Models\GymClass;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class WaitlistSpotAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $gymClass;
    protected $booking;
    protected $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(GymClass $gymClass, Booking $booking, Carbon $expiresAt)
    {
        $this->gymClass = $gymClass;
        $this->booking = $booking;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Διαθέσιμη θέση στο μάθημα!',
            'message' => "Μια θέση ελευθερώθηκε στο μάθημα {$this->gymClass->name} στις {$this->gymClass->date->format('d/m/Y')} {$this->gymClass->time}. Η κράτησή σας επιβεβαιώθηκε αυτόματα!",
            'class_id' => $this->gymClass->id,
            'class_name' => $this->gymClass->name,
            'class_date' => $this->gymClass->date->format('Y-m-d'),
            'class_time' => $this->gymClass->time,
            'booking_id' => $this->booking->id,
            'expires_at' => $this->expiresAt->toISOString(),
            'action_url' => '/bookings/' . $this->booking->id,
            'icon' => 'calendar-check'
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