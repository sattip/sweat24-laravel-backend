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
        $gymClass = $this->booking->gymClass;

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
            ->subject('Booking Cancelled - Sweat93')
            ->view('emails.bookings.booking-cancelled', [
                'booking' => $this->booking,
                'user' => $notifiable,
                'gymClass' => $gymClass,
                'instructorName' => $instructorName,
                'reason' => $this->reason,
                'cancelledBy' => $this->cancelledBy,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $gymClass = $this->booking->gymClass;
        $className = $gymClass ? $gymClass->name : 'Μάθημα';
        $classDate = $gymClass && $gymClass->date ? $gymClass->date->format('d/m/Y') : 'TBA';
        $classTime = $gymClass ? $gymClass->time : 'TBA';

        // Handle instructor name safely
        $instructorName = null;
        if ($gymClass) {
            if ($gymClass->instructor instanceof \App\Models\Instructor) {
                $instructorName = $gymClass->instructor->name;
            } elseif (is_string($gymClass->instructor)) {
                $instructorName = $gymClass->instructor;
            }
        }

        $message = "Η κράτησή σας για το μάθημα \"" . $className . "\" στις " . $classDate . " στις " . $classTime . " ακυρώθηκε.";

        if ($this->reason) {
            $message .= " Λόγος: {$this->reason}";
        }

        return [
            'title' => 'Ακύρωση κράτησης',
            'message' => $message,
            'booking_id' => $this->booking->id,
            'class_id' => $gymClass ? $gymClass->id : null,
            'class_name' => $className,
            'class_date' => $gymClass && $gymClass->date ? $gymClass->date->format('Y-m-d') : null,
            'class_time' => $classTime,
            'instructor_name' => $instructorName,
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