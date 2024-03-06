<?php

namespace hws\naplo;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

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
        return Cache::has('debug_on');
    }

    protected function write($record): void
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

        if (array_key_exists('channel', $record['context'])) $channel = $record['context']['channel'];
        // dd ((BORDER_PATH_BORDERDOC . $modul_azon . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $channel . '-' . date('Y-m-d') . '.log'));
        file_put_contents(BORDER_PATH_BORDERDOC . config('modul_azon') . DIRECTORY_SEPARATOR.'logs' . DIRECTORY_SEPARATOR . $channel . '-' . date('Y-m-d') . '.log', $record['formatted']."\n", FILE_APPEND | LOCK_EX);
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

            $tmpsql = preg_replace("/`password` = '([^']*?)\'/", '`password` = **** ', $tmpsql);
            $tmpsql = preg_replace("/`api_token` = '([^']*?)\'/", '`api_token` = **** ', $tmpsql);
            $tmpsql = preg_replace("/`remember_token` = '([^']*?)\'/", '`remember_token` = **** ', $tmpsql);
            $tmpsql = preg_replace("/`cookie_token` = '([^']*?)\'/", '`cookie_token` = **** ', $tmpsql);
            $tmpsql = preg_replace("/`old_passwords` = '([^']*?)\'/", '`old_passwords` = **** ', $tmpsql);
            $tmpsql = preg_replace("/`activation_token` = '([^']*?)\'/", '`activation_token` = **** ', $tmpsql);

            self::$sqls[] = $tmpsql;
        });
    }

    static function request_log()
    {

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
                self::cutFile($input);
                $ct .= "INPUT:\n" . print_r($input, true) . "\n";
            }

            logger()->debug($ct);
        });
    }

    static function response_log()
    {
        self::$do_response_log = true;
        Event::listen(RequestHandled::class, function ($event) {
            
            if (!empty(self::$sqls)) logger()->debug("SQL_LOG:\n\n" . implode("\n" . str_repeat('-', 50) . "\n", self::$sqls));
            
            if (self::$do_response_log == true) return;
            if ($event->response instanceof JsonResponse) {
                $ct = $event->response->content();
                //kivágjuk a file tartalmakat
                self::cutFile($ct);
                logger()->debug("RESPONSE_LOG: \n" . $ct);
            }
        });
    }

    static function cutFile(&$txt)
    {
        $txt = preg_replace('/"file_content":"([^"]*?)"/', '"file_content":"file_replaced",', $txt);
        $txt = preg_replace('/"password":"([^"]*?)"/', '"password":"****",', $txt);
        $txt = preg_replace('/"password2":"([^"]*?)"/', '"password2":"****",', $txt);
        $txt = preg_replace('/"current_password":"([^"]*?)"/', '"current_password":"****",', $txt);
        $txt = preg_replace('/"password_confirmation":"([^"]*?)"/', '"password_confirmation":"****",', $txt);
        $txt = preg_replace('/"token":"([^"]*?)"/', '"token":"****",', $txt);
        $txt = preg_replace('/"activate_token":"([^"]*?)"/', '"activate_token":"****",', $txt);
    }
}
