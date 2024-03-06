<?php

namespace hws\naplo;

use Monolog\Logger;

class CustomLogger
{
    public function __invoke()
    {
        return new Logger(
            'HWSLOG',
            [
                new CustomLoggerHandler(),
            ]
        );
    }
}
