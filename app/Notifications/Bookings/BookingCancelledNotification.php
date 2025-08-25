<?php

namespace App\Notifications\Bookings;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $reason;
    protected $cancelledBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $reason = null, string $cancelledBy = 'user')
    {
        $this->booking = $booking;
        $this->reason = $reason;
        $this->cancelledBy = $cancelledBy; // 'user', 'admin', 'system'
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
            ->subject('Booking Cancelled - Sweat93')
            ->view('emails.bookings.booking-cancelled', [
                'booking' => $this->booking->load('gymClass.instructor'),
                'user' => $notifiable,
                'gymClass' => $this->booking->gymClass,
                'reason' => $this->reason,
                'cancelledBy' => $this->cancelledBy,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $message = "Η κράτησή σας για το μάθημα \"{$this->booking->gymClass->name}\" στις {$this->booking->gymClass->date->format('d/m/Y')} στις {$this->booking->gymClass->time} ακυρώθηκε.";
        
        if ($this->reason) {
            $message .= " Λόγος: {$this->reason}";
        }

        return [
            'title' => 'Ακύρωση κράτησης',
            'message' => $message,
            'booking_id' => $this->booking->id,
            'class_id' => $this->booking->gymClass->id,
            'class_name' => $this->booking->gymClass->name,
            'class_date' => $this->booking->gymClass->date->format('Y-m-d'),
            'class_time' => $this->booking->gymClass->time,
            'instructor_name' => $this->booking->gymClass->instructor->name ?? null,
            'booking_status' => $this->booking->status,
            'cancellation_reason' => $this->reason,
            'cancelled_by' => $this->cancelledBy,
            'action_url' => '/classes',
            'icon' => 'calendar-x',
            'type' => 'booking_cancelled'
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