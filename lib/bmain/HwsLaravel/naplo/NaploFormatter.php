<?php

namespace hws\naplo;

use Illuminate\Support\Facades\Config;
use Monolog\Formatter\JsonFormatter;

class NaploFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
                if (!config('request_id')) Config::set('request_id', uniqid());
                $out = [
                    'datetime' => $record['datetime']->format('Y-m-d H:i:s'),
                    'software_id' => config('software_id') . (config('software_id') && config('modul_azon') ? '_' : '') . config('modul_azon'),
                    'request_id' => config('request_id'),
                    'user_login' => config('user_login', '-'),
                    'user_name' => config('user_name', 'Technikai felhasználó'),
                    'uuid' => config('uuid', '-'),
                    'uri' => urldecode(config('cmd_uri') ?: (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : implode(' ', $_SERVER['argv']))),
                    'method' => (isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : 'CMD'),
                    'event' => '-',
                    'code' => '-',
                    'error' => '-',
                    'message' => $record['message'],
                ];
                if (array_key_exists('event', $record['context'])) $out['event'] = $record['context']['event'];
                if (array_key_exists('code', $record['context'])) $out['code'] = $record['context']['code'];
                if (array_key_exists('error', $record['context'])) $out['error'] = $record['context']['error'];
                if (array_key_exists('software_id', $record['context'])) $out['software_id'] = $record['context']['software_id'];
                if (array_key_exists('user_login', $record['context'])) $out['user_login'] = $record['context']['user_login'];
                if (array_key_exists('user_name', $record['context'])) $out['user_name'] = $record['context']['user_name'];
                if (array_key_exists('uuid', $record['context'])) $out['uuid'] = $record['context']['uuid'];
                if (array_key_exists('uri', $record['context'])) $out['uri'] = $record['context']['uri'];
                if (array_key_exists('method', $record['context'])) $out['method'] = $record['context']['method'];
                return $out;
            });
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
