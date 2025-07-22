<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Listeners\UpdateClassParticipants;
use App\Listeners\ProcessSessionDeduction;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingCreated::class => [
            UpdateClassParticipants::class,
            ProcessSessionDeduction::class,
        ],
        BookingCancelled::class => [
            UpdateClassParticipants::class,
            ProcessSessionDeduction::class,
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
