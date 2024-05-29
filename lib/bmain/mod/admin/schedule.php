<?php

$schedule->command('cache:clear')->weekly();
$schedule->command('clearCacheVue')->weekly();
$schedule->command('DeletempFiles')->weekly();
