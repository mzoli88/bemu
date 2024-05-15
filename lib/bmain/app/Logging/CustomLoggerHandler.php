<?php

namespace App\Logging;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Monolog\LogRecord;

class CustomLoggerHandler extends AbstractProcessingHandler
{

    static $do_response_log = false;
    static $do_request_log = false;
    static $do_sql_log = false;
    static $sqls = [];

    public function __construct()
    {
        $this->formatter = new DefaultFormatter();
    }

    static function canDebug()
    {
        return Cache::remember('log_debug', now()->addMinutes(10), function () {
            try {
                if (DB::getDefaultConnection() && Schema::hasTable('admin_params')) {
                    return getParam('log_debug', 'admin') == 'I';
                } else {
                    return false;
                }
            } catch (\Throwable $th) {
                report($th);
                return false;
            }
        });
    }

    protected function write(LogRecord $record): void
    {
        $channel = 'debug';
        if (array_key_exists('channel', $record->context)) $channel = $record->context['channel'];
        $modul_azon = getModulAzon();
        if (array_key_exists('modul_azon', $record->context)) $modul_azon = $record->context['modul_azon'];

        if($channel=='debug'){
            switch ($record['level_name']) {
                case 'EMERGENCY':
                case 'ALERT':
                case 'CRITICAL':
                case 'ERROR':
                    $channel = 'error';
                    break;
            }
        }

        switch ($channel) {
            case 'debug':
                if (!self::canDebug()) return;
                $formatted = self::formatDebug($record);
                file_put_contents(BORDER_PATH_BORDERDOC . 'bmain_logs' . DIRECTORY_SEPARATOR . $modul_azon . '-debug-' . date('Y-m-d') . '.log', $formatted . "\n", FILE_APPEND | LOCK_EX);
                break;
            case 'error':
                if (self::canDebug()) {
                    $formatted = self::formatDebug($record,'debug');
                    file_put_contents(BORDER_PATH_BORDERDOC . 'bmain_logs' . DIRECTORY_SEPARATOR . $modul_azon . '-debug-' . date('Y-m-d') . '.log', $formatted . "\n", FILE_APPEND | LOCK_EX);
                }
                $formatted = self::formatDebug($record, 'error');
                file_put_contents(BORDER_PATH_BORDERDOC . 'bmain_logs' . DIRECTORY_SEPARATOR . $modul_azon . '-error-' . date('Y-m-d') . '.log', $formatted . "\n", FILE_APPEND | LOCK_EX);
                break;
            default:
                $formatted = self::formatChanel($record);
                file_put_contents(BORDER_PATH_BORDERDOC . 'bmain_logs' . DIRECTORY_SEPARATOR . $modul_azon . '-' . $channel . '-' . date('Y-m-d') . '.log', $formatted . "\n", FILE_APPEND | LOCK_EX);
                break;
        }
    }

    static function sql_log()
    {
        if (self::$do_sql_log == true) return;
        if (empty(DB::getDefaultConnection())) return;
        self::$do_sql_log = true;

        DB::listen(function ($query) {
            $bindings = array_map(
                function ($value) {
                    return is_numeric($value) ? $value : "'{$value}'";
                },
                $query->bindings
            );
            $tmpsql = Str::replaceArray(
                '?',
                $bindings,
                $query->sql
            );

            foreach (config('hiddenfields', ['password']) as $prop) $tmpsql = preg_replace("/`" . $prop . "` = '([^']*?)\'/", '`' . $prop . '` = **** ', $tmpsql);

            self::$sqls[] = $tmpsql;
            if (count(self::$sqls) > 40) {
                // ne tömje tele a memóriát ha sok adatbázis művelet van
                logger()->debug("SQL_LOG:\n\n" . implode("\n" . str_repeat('-', 50) . "\n", self::$sqls));
                self::$sqls = [];
            }
        });
    }

    static function request_log()
    {
        if (app()->runningInConsole()) return;
        if (self::$do_request_log == true) return;
        self::$do_request_log = true;

        Event::listen(RequestHandled::class, function () {
            $ct = "REQUEST_LOG:\n\n";

            if (!empty($_GET)) $ct .= "GET:\n" . print_r($_GET, true) . "\n";
            if (!empty($_POST)) $ct .= "POST:\n" . print_r($_POST, true) . "\n";

            if (!app()->runningInConsole()) {
                $headers = getallheaders();
                if (array_key_exists('Authorization', $headers)) $headers['Authorization'] = 'Bearer ***';
                if (array_key_exists('Cookie', $headers)) $headers['Cookie'] = '***';
                $ct .= "HEADERS:\n" . print_r($headers, true) . "\n";
            }

            $input = file_get_contents("php://input");
            if (!empty($input)) {
                $ct .= "INPUT:\n" . print_r($input, true) . "\n";
            }

            logger()->debug($ct);
        });
    }

    static function response_log()
    {
        if (self::$do_response_log == true) return;
        self::$do_response_log = true;
        Event::listen(RequestHandled::class, function ($event) {
            // dump (self::$sqls);

            if (!empty(self::$sqls)) logger()->debug("SQL_LOG:\n\n" . implode("\n" . str_repeat('-', 50) . "\n", self::$sqls));

            if (app()->runningInConsole()) return;
            if ($event->response instanceof JsonResponse) {

                $ct = "StatusCode: " . $event->response->getStatusCode();
                $ct .= "\nContent:\n" . $event->response->content();

                logger()->debug("RESPONSE_LOG: \n" . $ct);
            }
        });
    }

    static function formatChanel(LogRecord $record)
    {
        if (!config('request_id', false)) {
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
            'category' => $record['context']['channel'] ?? 'audit',
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
        return json_encode($out);
    }

    static function formatDebug(LogRecord $record, $channel = "debug")
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
            'category' => $channel,
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
        return json_encode($out);
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
        foreach (config('hiddenfields', ['password']) as $prop) {
            $string = preg_replace('#(\s*[\"\']' . $prop . '[\"\']\s*:\s*)[^\,\}]*#', '$1"***"', $string);
            $string = preg_replace('#(\s*[\[\"\']' . $prop . '[\]\"\']\s*\=\>\s*).*#', '$1***', $string);
        }
        return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
    }
}
