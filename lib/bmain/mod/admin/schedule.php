<?php

$schedule->command('CheckInactiveUsers')
    ->withoutOverlapping()
    ->dailyAt('1');

// Minden vasárnap törölje a cahce-t
$schedule->command('cache:clear')->sundays();
$schedule->command('clearCacheVue')->sundays();
