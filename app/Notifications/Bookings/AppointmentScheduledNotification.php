<?php

namespace App\Notifications\Bookings;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $appointmentType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $appointmentType = 'personal_training')
    {
        $this->booking = $booking;
        $this->appointmentType = $appointmentType; // 'personal_training', 'consultation', 'assessment'
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
            ->subject('Appointment Scheduled - Sweat93')
            ->view('emails.bookings.appointment-scheduled', [
                'booking' => $this->booking,
                'user' => $notifiable,
                'gymClass' => $this->booking->gymClass,
                'appointmentType' => $this->appointmentType,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $gymClass = $this->booking->gymClass;

        $typeNames = [
            'personal_training' => 'Προσωπική Προπόνηση',
            'consultation' => 'Συμβουλευτική',
            'assessment' => 'Αξιολόγηση'
        ];

        $typeName = $typeNames[$this->appointmentType] ?? 'Ραντεβού';
        $instructorName = ($gymClass && $gymClass->instructor) ? $gymClass->instructor->name : 'προπονητή';
        $classDate = $gymClass && $gymClass->date ? $gymClass->date->format('d/m/Y') : 'TBA';
        $classTime = $gymClass ? $gymClass->time : 'TBA';

        return [
            'title' => 'Προγραμματισμός ραντεβού',
            'message' => "Το ραντεβού σας για {$typeName} με τον/την {$instructorName} προγραμματίστηκε για τις {$classDate} στις {$classTime}.",
            'booking_id' => $this->booking->id,
            'class_id' => $gymClass ? $gymClass->id : null,
            'appointment_type' => $this->appointmentType,
            'appointment_type_name' => $typeName,
            'class_date' => $gymClass && $gymClass->date ? $gymClass->date->format('Y-m-d') : null,
            'class_time' => $classTime,
            'instructor_name' => ($gymClass && $gymClass->instructor) ? $gymClass->instructor->name : null,
            'instructor_id' => ($gymClass && $gymClass->instructor) ? $gymClass->instructor->id : null,
            'booking_status' => $this->booking->status,
            'action_url' => '/appointments/' . $this->booking->id,
            'icon' => 'user-check',
            'type' => 'appointment_scheduled'
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