<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('doses are generated every hour without overlapping runs', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'doses:generate'));

    expect($event)->not->toBeNull();
    expect($event->expression)->toBe('0 * * * *');
    expect($event->withoutOverlapping)->toBeTrue();
});
