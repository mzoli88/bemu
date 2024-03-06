<?php

namespace hws;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Fnaplo;

class NaploLogger extends AbstractProcessingHandler
{

    public function __invoke(array $config)
    {
        $logger = new Logger('naplo');
        $logger->pushHandler(new NaploLogger());
        return $logger;
    }

    protected function write(array $record): void
    {
        switch ($record['level_name']) {
            case 'EMERGENCY':
            case 'ALERT':
            case 'CRITICAL':
            case 'ERROR':
                if (!empty($record['context']) && !empty($record['context']['exception'])) {
                    Fnaplo::error($record['context']['exception']);
                } else {
                    Fnaplo::error($record['message']);
                }
                break;
            case 'INFO':
                Fnaplo::info($record['message']);
                break;
            default:
                Fnaplo::debug($record['message']);
                break;
        }
    }
}
