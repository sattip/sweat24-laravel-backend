<?php

namespace App\Events;

use App\Models\User;
use App\Models\GymClass;
use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaitlistSpotAvailable
{
    use Dispatchable, SerializesModels;

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


} 