<?php

namespace App\Logging;

use Monolog\Logger;

class CustomLogger
{
    public function __invoke(): Logger
    {
        return new Logger(
            'HWSLOG',
            [
                new CustomLoggerHandler(),
            ]
        );
    }
}
