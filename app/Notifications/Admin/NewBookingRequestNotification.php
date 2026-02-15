<?php

namespace App\Notifications\Admin;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;
    protected $requestType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $requestType = 'class_booking')
    {
        $this->booking = $booking;
        $this->requestType = $requestType; // 'class_booking', 'personal_training', 'consultation'
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
        $adminUrl = config('app.url') . '/admin/bookings/' . $this->booking->id;

        return (new MailMessage)
            ->subject('New Booking Request - Sweat93')
            ->view('emails.admin.new-booking-request', [
                'booking' => $this->booking,
                'user' => $this->booking->user,
                'gymClass' => $this->booking->gymClass,
                'admin' => $notifiable,
                'requestType' => $this->requestType,
                'adminUrl' => $adminUrl,
            ]);
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $typeNames = [
            'class_booking' => 'Κράτηση μαθήματος',
            'personal_training' => 'Προσωπική προπόνηση',
            'consultation' => 'Συμβουλευτική'
        ];

        $typeName = $typeNames[$this->requestType] ?? 'Κράτηση';

        $requiresApproval = in_array($this->booking->status, ['pending', 'requested']);

        return [
            'title' => 'Νέα αίτηση κράτησης',
            'message' => "Ο χρήστης {$this->booking->user->first_name} {$this->booking->user->last_name} έκανε αίτηση για {$typeName} στις {$this->booking->gymClass->date->format('d/m/Y')} στις {$this->booking->gymClass->time}.",
            'booking_id' => $this->booking->id,
            'user_id' => $this->booking->user->id,
            'user_name' => $this->booking->user->first_name . ' ' . $this->booking->user->last_name,
            'user_email' => $this->booking->user->email,
            'user_phone' => $this->booking->user->phone,
            'class_id' => $this->booking->gymClass->id,
            'class_name' => $this->booking->gymClass->name,
            'class_date' => $this->booking->gymClass->date->format('Y-m-d'),
            'class_time' => $this->booking->gymClass->time,
            'instructor_name' => $this->booking->gymClass->instructor->name ?? null,
            'booking_status' => $this->booking->status,
            'request_type' => $this->requestType,
            'request_type_name' => $typeName,
            'requires_approval' => $requiresApproval,
            'booking_date' => $this->booking->created_at->format('Y-m-d H:i:s'),
            'action_url' => '/admin/bookings/' . $this->booking->id,
            'icon' => 'calendar-plus',
            'type' => 'new_booking_request',
            'priority' => $requiresApproval ? 'high' : 'normal'
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