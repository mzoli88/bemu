<?php

// $schedule->command('notify:EmailReSend')->withoutOverlapping()->everyMinutes();
if(config('isBorder')){
    collect(entities())->each(function ($entity) use ($schedule) {
        $schedule->command('queue:work --stop-when-empty --tries=3 --queue=email_high_' . $entity['value'])
        ->everyMinute()
        ->withoutOverlapping();
        $schedule->command('queue:work --stop-when-empty --tries=3 --queue=email_low_' . $entity['value'])
        ->everyMinute()
        ->withoutOverlapping();
    });
}

$schedule->command('nc:FailedEmailsDelete')
    ->withoutOverlapping()
    ->dailyAt('1:00');

$schedule->command('nc:SendedEmailsDelete')
    ->withoutOverlapping()
    ->dailyAt('1:10');
