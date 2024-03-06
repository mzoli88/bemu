<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Illuminate\Support\Facades\Config;
use Monolog\LogRecord;
use Illuminate\Support\Str;

class DefaultFormatter extends BaseJsonFormatter
{

    public function format(LogRecord $record): string
    {
        $channel = 'debug';
        if (array_key_exists('channel', $record->context)) $channel = $record->context['channel'];
        if ($channel == 'debug') {
            return $this->formatDebug($record);
        } else {
            return $this->formatChanel($record);
        }
    }

    public function formatChanel(LogRecord $record)
    {
        if(!config('request_id',false)){
            if (request()->headers->get('request-id')) {
                config(['request_id' => request()->headers->get('request-id')]);
            } else {
                config(['request_id' => uniqid()]);
            }
        }
        $user = getUser();

        $userName = 'Nem bejelentkezett felhasználó';
        if ($user) {
            $userName = $user->name;
        } else {
            if (app()->runningInConsole()) {
                $userName = 'Technikai felhasználó';
            } else {
                $route = request()->route();
                if ($route) {
                    $routeArray = explode('/', $route->uri);
                    if ($routeArray[1] == 'interfaces') {
                        $userName = 'Technikai felhasználó';
                    }
                }                                 
            }
        }
        
        $out = [
            'datetime' => $record['datetime']->format('Y-m-d H:i:s'),
            'software_id' => getModulName() . ' v' . getModulVersion(),
            'request_id' => config('request_id'),
            'user_login' => $user ? $user->login : '-',
            'user_name' => $userName,
            'uuid' => $user ? config('request_id') : '-',
            'uri' => urldecode(isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : implode(' ', $_SERVER['argv'])),
            'method' => (isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : 'CMD'),
            'event' => '-',
            'code' => '-',
            'entity_id' => getEntity() ?: '-',
            'entity_name' => getEntityName() ?: '-',
            'error' => '-',
            'message' => self::truncate($record['message']),
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
        if (array_key_exists('entity_id', $record['context'])) {
            $out['entity_id'] = $record['context']['entity_id'];
            $out['entity_name'] = getEntityName($record['context']['entity_id']);
        }
        if (array_key_exists('modul_azon', $record['context'])) {
            $out['software_id'] = getModulName($record['context']['modul_azon']) . ' v' . getModulVersion($record['context']['modul_azon']);
        }
        return $this->toJson($out);
    }

    public function formatDebug(LogRecord $record)
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
        $user = getUser();
        $out = [
            'datetime' => $record['datetime']->format('Y-m-d H:i:s'),
            'level' => $level,
            'software_id' => getModulName() . ' ' . getModulVersion(),
            'request_id' => config('request_id'),
            'user_id' => $user ? '' . $user->id : '-',
            'user_login' => $user ? $user->login : '-',
            'user_name' => $user ? $user->name : (app()->runningInConsole() ? 'Technikai felhasználó' : 'Nem bejelentkezett felhasználó'),
            'uuid' => $user ? config('request_id') : '-',
            'entity_id' => getEntity(),
            'entity_name' => getEntityName(),
            // 'modul_azon' => getModulAzon(),
            'uri' => urldecode(isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : implode(' ', $_SERVER['argv'])),
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
        $string = preg_replace('/"file_content":"([^"]*?)"/', '"file_content":"file_replaced",', $string);
        foreach(config('hiddenfields',['password']) as $prop){
            $string = preg_replace('#(\s*[\"\']'.$prop.'[\"\']\s*:\s*)[^\,\}]*#', '$1"***"', $string);
            $string = preg_replace('#(\s*[\[\"\']'.$prop.'[\]\"\']\s*\=\>\s*).*#', '$1***', $string);
        } 
        return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
    }
}
