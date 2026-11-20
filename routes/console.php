<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Locally: run `php artisan schedule:work` (or `composer dev`).
| In production: a single cron entry runs `php artisan schedule:run` every minute.
|
*/

Schedule::command('doses:generate')
    ->hourly()
    ->withoutOverlapping();
