<?php

namespace App\Logging;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Schema;
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
                report ($th);
                return false;
            }
        });
    }

    protected function write(LogRecord $record): void
    {

        switch ($record['level_name']) {
            case 'EMERGENCY':
            case 'ALERT':
            case 'CRITICAL':
            case 'ERROR':
                self::request_log();
                self::sql_log();
                self::response_log();
                break;
            case 'DEBUG':
                if (!self::canDebug()) return; //nincs naplózás
                break;
        }


        $channel = 'debug';
        $modul_azon = getModulAzon();

        if (array_key_exists('channel', $record->context)) $channel = $record->context['channel'];
        if (array_key_exists('modul_azon', $record->context)) $modul_azon = $record->context['modul_azon'];

        file_put_contents(storage_path('bmain_logs' . DIRECTORY_SEPARATOR . $modul_azon . '-' . $channel . '-' . date('Y-m-d') . '.log'), $record->formatted . "\n", FILE_APPEND | LOCK_EX);
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

            foreach(config('hiddenfields',['password']) as $prop) $tmpsql = preg_replace("/`".$prop."` = '([^']*?)\'/", '`'.$prop.'` = **** ', $tmpsql);

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

                $ct = "StatusCode: ".$event->response->getStatusCode();
                $ct .= "\nContent:\n".$event->response->content();

                logger()->debug("RESPONSE_LOG: \n" . $ct);
            }
        });
    }

}
