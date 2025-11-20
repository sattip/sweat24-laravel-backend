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
        // Ensure all necessary relationships are loaded
        $booking = $this->booking->load(['gymClass', 'user']);
        $gymClass = $booking->gymClass;

        // Handle instructor data - it might be a string or an object
        $instructorName = 'TBA';
        if ($gymClass) {
            if ($gymClass->instructor instanceof \App\Models\Instructor) {
                $instructorName = $gymClass->instructor->name;
            } elseif (is_string($gymClass->instructor)) {
                $instructorName = $gymClass->instructor;
            }
        }

        return (new MailMessage)
            ->subject('Κράτηση Επιβεβαιώθηκε - Sweat93')
            ->view('emails.bookings.booking-confirmation', [
                'booking' => $booking,
                'user' => $notifiable,
                'gymClass' => $gymClass,
                'instructorName' => $instructorName,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $gymClass = $this->booking->gymClass;

        $className = $gymClass ? ($gymClass->name ?? $this->booking->class_name ?? 'Μάθημα') : ($this->booking->class_name ?? 'Μάθημα');
        $classDate = $gymClass && $gymClass->date ? $gymClass->date->format('d/m/Y') : ($this->booking->date ?? 'TBA');
        $classTime = $gymClass ? ($gymClass->time ?? $this->booking->time ?? 'TBA') : ($this->booking->time ?? 'TBA');

        // Handle instructor name safely
        $instructorName = null;
        if ($gymClass) {
            if ($gymClass->instructor instanceof \App\Models\Instructor) {
                $instructorName = $gymClass->instructor->name;
            } elseif (is_string($gymClass->instructor)) {
                $instructorName = $gymClass->instructor;
            }
        }

        return [
            'title' => 'Επιβεβαίωση κράτησης',
            'message' => "Η κράτησή σας για το μάθημα \"" . $className . "\" " .
                        "στις " . $classDate . " " .
                        "στις " . $classTime . " επιβεβαιώθηκε με επιτυχία.",
            'booking_id' => $this->booking->id,
            'class_id' => $gymClass ? $gymClass->id : null,
            'class_name' => $className,
            'class_date' => $gymClass && $gymClass->date ? $gymClass->date->format('Y-m-d') : ($this->booking->date ?? null),
            'class_time' => $classTime,
            'instructor_name' => $instructorName ?? ($this->booking->instructor ?? null),
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