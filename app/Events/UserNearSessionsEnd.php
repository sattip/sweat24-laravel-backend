<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNearSessionsEnd implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $userPackage;
    public $remainingSessions;
    public $isLastSession;

    public function __construct(User $user, UserPackage $userPackage, int $remainingSessions)
    {
        $this->user = $user;
        $this->userPackage = $userPackage;
        $this->remainingSessions = $remainingSessions;
        $this->isLastSession = $remainingSessions <= 1;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id),
        ];
    }
} 