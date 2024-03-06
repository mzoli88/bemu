<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

date_default_timezone_set('Europe/Budapest');

require 'bmain/vendor/autoload.php';

require_once('config.class.php');

$app = require_once 'bmain/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
