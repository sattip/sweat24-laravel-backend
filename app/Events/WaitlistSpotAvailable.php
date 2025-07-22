<?php

namespace App\Events;

use App\Models\User;
use App\Models\GymClass;
use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaitlistSpotAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $gymClass;
    public $booking;
    public $expiresAt;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, GymClass $gymClass, Booking $booking, $expiresAt)
    {
        $this->user = $user;
        $this->gymClass = $gymClass;
        $this->booking = $booking;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'Μια θέση ελευθερώθηκε στο μάθημα ' . $this->gymClass->name,
            'class_name' => $this->gymClass->name,
            'class_date' => $this->gymClass->date->format('Y-m-d'),
            'class_time' => $this->gymClass->time,
            'booking_id' => $this->booking->id,
            'expires_at' => $this->expiresAt->toISOString(),
        ];
    }
} 