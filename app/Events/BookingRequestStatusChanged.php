<?php

namespace App\Events;

use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRequestStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bookingRequest;
    public $previousStatus;
    public $newStatus;

    public function __construct(BookingRequest $bookingRequest, string $previousStatus, string $newStatus)
    {
        $this->bookingRequest = $bookingRequest;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        
        // Send to user if they exist
        if ($this->bookingRequest->user_id) {
            $channels[] = new PrivateChannel('booking-request.' . $this->bookingRequest->user_id);
        }
        
        return $channels;
    }

    /**
     * Check if this is a confirmation
     */
    public function isConfirmed(): bool
    {
        return $this->newStatus === BookingRequest::STATUS_CONFIRMED;
    }

    /**
     * Check if this is a rejection
     */
    public function isRejected(): bool
    {
        return $this->newStatus === BookingRequest::STATUS_REJECTED;
    }
} 