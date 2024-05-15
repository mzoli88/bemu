<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Monolog\LogRecord;

class DefaultFormatter extends BaseJsonFormatter
{

    public function format(LogRecord $record): string
    {
        //feleslegesen ne végezzen formázást
        return '';
    }

}
