<?php

namespace hws\naplo;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Illuminate\Support\Facades\Config;

class DefaultFormatter extends BaseJsonFormatter
{

    public function format($record): string
    {
        $channel = 'debug';
        if (array_key_exists('channel', $record['context'])) $channel = $record['context']['channel'];
        if ($channel == 'debug') {
            return $this->formatDebug($record);
        } else {
            return $this->formatChanel($record);
        }
    }

    public function formatChanel($record)
    {
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
        return $this->toJson($out);
    }

    public function formatDebug($record)
    {
        $message = $record['message'];
        if (!config('request_id')) Config::set('request_id', uniqid());
        switch ($record['level_name']) {
            case 'EMERGENCY':
            case 'ALERT':
            case 'CRITICAL':
            case 'ERROR':
                // $handler->setLevel(Logger::DEBUG);
                // self::start(true);
                if (!empty($record['context']) && !empty($record['context']['exception'])) {
                    $message = self::formatThrowable($record['context']['exception']);
                }
                $level = 'error';
                break;
            case 'INFO':
                $level = 'info';
                break;
            default:
                $level = 'debug';
                break;
        }
        $out = [
            'datetime' => $record['datetime']->format('Y-m-d H:i:s'),
            'level' => $level,
            'software_id' => config('software_id') . (config('software_id') && config('modul_azon') ? '_' : '') . config('modul_azon'),
            'request_id' => config('request_id'),
            'user_id' => config('user_id', '-'),
            'user_login' => config('user_login', '-'),
            'user_name' => config('user_name', 'Technikai felhasználó'),
            'uuid' => config('uuid', '-'),
            'uri' => urldecode(config('cmd_uri') ?: (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : implode(' ', $_SERVER['argv']))),
            'method' => (isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : 'CMD'),
            'ip' => (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '-'),
            'microtime' => microtime(true),
            'past_time' => sprintf('%0.4f', (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"])), //eltelt idő
            'message' => self::truncate($message),
        ];
        return $this->toJson($out);
    }

    static function formatThrowable($th)
    {
        return $th->getMessage()
            . " (" . get_class($th) . ", code: " . $th->getCode() . ")\n"
            . $th->getFile()
            . " (Line:" . $th->getLine() . ")\n"
            . "\nTrace:\n" . $th->getTraceAsString();
    }

    static function truncate($string, $length = 30000, $dots = " ... (túl hosszú a bejegyzés)")
    {
        return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
    }
}
