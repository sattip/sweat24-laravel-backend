<?php

namespace App\Notifications\BookingRequests;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingRequestNotification extends Notification implements ShouldQueue
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
            'ems' => 'EMS',
            'personal' => 'Personal Training'
        ];

        $serviceTypeName = $serviceTypeNames[$this->bookingRequest->service_type] ?? 'Service';

        return (new MailMessage)
            ->subject('New Booking Request - Sweat93')
            ->view('emails.booking-requests.new-request', [
                'bookingRequest' => $this->bookingRequest,
                'admin' => $notifiable,
                'serviceTypeName' => $serviceTypeName,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $serviceTypeNames = [
            'ems' => 'EMS',
            'personal' => 'Personal Training'
        ];

        $serviceTypeName = $serviceTypeNames[$this->bookingRequest->service_type] ?? 'Service';

        return [
            'title' => 'New Booking Request',
            'message' => "New {$serviceTypeName} booking request from {$this->bookingRequest->client_name} ({$this->bookingRequest->client_email})",
            'booking_request_id' => $this->bookingRequest->id,
            'client_name' => $this->bookingRequest->client_name,
            'client_email' => $this->bookingRequest->client_email,
            'client_phone' => $this->bookingRequest->client_phone,
            'service_type' => $this->bookingRequest->service_type,
            'service_type_name' => $serviceTypeName,
            'instructor_name' => $this->bookingRequest->instructor->name ?? null,
            'preferred_time_slots' => $this->bookingRequest->preferred_time_slots,
            'notes' => $this->bookingRequest->notes,
            'status' => $this->bookingRequest->status,
            'created_at' => $this->bookingRequest->created_at->toISOString(),
            'action_url' => '/admin/booking-requests/' . $this->bookingRequest->id,
            'icon' => 'calendar-plus',
            'type' => 'new_booking_request',
            'priority' => 'high'
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