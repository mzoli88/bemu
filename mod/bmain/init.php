<?php

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

require_once('belepve.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
$out = [];

define('LARAVEL_START', microtime(true));
date_default_timezone_set('Europe/Budapest');
require 'bmain/vendor/autoload.php';

$app = require_once 'bmain/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

define('INIT_RUN', true);

$output = new BufferedOutput;
Artisan::call('optimize:clear', [], $output);
Artisan::call('migrate --force', [], $output);
Artisan::call('optimize', [], $output);
// echo 'A telepites konyvtara: ' . BORDER_PATH_BORDERLIB . '<br>';
echo str_replace("\n", '<br>', $output->fetch());
