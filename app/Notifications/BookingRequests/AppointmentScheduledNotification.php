<?php

namespace App\Notifications\BookingRequests;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bookingRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(BookingRequest $bookingRequest)
    {
        $this->bookingRequest = $bookingRequest;
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
        $serviceTypeNames = [
            'ems' => 'EMS Training',
            'personal' => 'Personal Training'
        ];

        $serviceTypeName = $serviceTypeNames[$this->bookingRequest->service_type] ?? 'Appointment';

        return (new MailMessage)
            ->subject('Appointment Scheduled - Sweat93')
            ->view('emails.booking-requests.appointment-scheduled', [
                'bookingRequest' => $this->bookingRequest,
                'user' => $notifiable,
                'serviceTypeName' => $serviceTypeName,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $serviceTypeNames = [
            'ems' => 'EMS Training',
            'personal' => 'Personal Training'
        ];

        $serviceTypeName = $serviceTypeNames[$this->bookingRequest->service_type] ?? 'Appointment';

        $confirmedDate = $this->bookingRequest->confirmed_date ?? 'TBA';
        $confirmedTime = $this->bookingRequest->confirmed_time ?? 'TBA';
        $instructorName = $this->bookingRequest->instructor ? $this->bookingRequest->instructor->name : '';

        return [
            'title' => 'Appointment Scheduled',
            'message' => "Your {$serviceTypeName} appointment has been scheduled for {$confirmedDate} at {$confirmedTime}" .
                        ($instructorName ? " with {$instructorName}" : ''),
            'booking_request_id' => $this->bookingRequest->id,
            'service_type' => $this->bookingRequest->service_type,
            'service_type_name' => $serviceTypeName,
            'confirmed_date' => $this->bookingRequest->confirmed_date,
            'confirmed_time' => $this->bookingRequest->confirmed_time,
            'instructor_name' => $this->bookingRequest->instructor ? $this->bookingRequest->instructor->name : null,
            'instructor_id' => $this->bookingRequest->instructor_id,
            'admin_notes' => $this->bookingRequest->admin_notes,
            'status' => $this->bookingRequest->status,
            'action_url' => '/booking-requests/' . $this->bookingRequest->id,
            'icon' => 'calendar-check',
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