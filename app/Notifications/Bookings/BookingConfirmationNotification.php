<?php

namespace App\Notifications\Bookings;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
            ->subject('Booking Confirmed - Sweat93')
            ->view('emails.bookings.booking-confirmation', [
                'booking' => $this->booking->load('gymClass.instructor'),
                'user' => $notifiable,
                'gymClass' => $this->booking->gymClass,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Επιβεβαίωση κράτησης',
            'message' => "Η κράτησή σας για το μάθημα \"{$this->booking->gymClass->name}\" στις {$this->booking->gymClass->date->format('d/m/Y')} στις {$this->booking->gymClass->time} επιβεβαιώθηκε με επιτυχία.",
            'booking_id' => $this->booking->id,
            'class_id' => $this->booking->gymClass->id,
            'class_name' => $this->booking->gymClass->name,
            'class_date' => $this->booking->gymClass->date->format('Y-m-d'),
            'class_time' => $this->booking->gymClass->time,
            'instructor_name' => $this->booking->gymClass->instructor->name ?? null,
            'booking_status' => $this->booking->status,
            'action_url' => '/bookings/' . $this->booking->id,
            'icon' => 'calendar-check',
            'type' => 'booking_confirmation'
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