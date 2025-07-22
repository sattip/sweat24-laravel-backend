<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule package expiry checks to run daily at 9 AM
Schedule::command('packages:check-expiry')->dailyAt('09:00');

// Schedule class evaluation emails daily at 10 AM
Schedule::command('evaluations:send')->dailyAt('10:00');

// Schedule data consistency check daily at 2 AM
Schedule::command('data:check-consistency --fix')->dailyAt('02:00');
