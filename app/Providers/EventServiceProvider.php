<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Events\OrderReadyForPickup;
use App\Events\UserNearSessionsEnd;
use App\Events\ChatMessageReceived;
use App\Events\EventCreated;
use App\Events\BookingRequestStatusChanged;
use App\Events\WaitlistSpotAvailable;
use App\Listeners\UpdateClassParticipants;
use App\Listeners\ProcessSessionDeduction;
use App\Listeners\SendOrderReadyNotification;
use App\Listeners\SendSessionsEndingNotification;
use App\Listeners\SendChatMessageNotification;
use App\Listeners\SendNewEventNotification;
use App\Listeners\SendBookingRequestStatusNotification;
use App\Listeners\SendWaitlistSpotNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Existing booking events
        BookingCreated::class => [
            UpdateClassParticipants::class,
            ProcessSessionDeduction::class,
        ],
        BookingCancelled::class => [
            UpdateClassParticipants::class,
            ProcessSessionDeduction::class,
        ],
        
        // New push notification events
        OrderReadyForPickup::class => [
            SendOrderReadyNotification::class,
        ],
        UserNearSessionsEnd::class => [
            SendSessionsEndingNotification::class,
        ],
        ChatMessageReceived::class => [
            SendChatMessageNotification::class,
        ],
        EventCreated::class => [
            SendNewEventNotification::class,
        ],
        BookingRequestStatusChanged::class => [
            SendBookingRequestStatusNotification::class,
        ],
        WaitlistSpotAvailable::class => [
            SendWaitlistSpotNotification::class,
        ],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
