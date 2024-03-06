<?php

namespace hws;

use hws\naplo\DefaultFormatter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;


class Web
{

    public function __construct()
    {

        Config::set('app.timezone', 'Europe/Budapest');
        date_default_timezone_set('Europe/Budapest');

        //ha debug bevan kapcsolva, loggoljon (request, sql, response)
        DefaultFormatter::start();
    }

    static function getJog($tmp)
    {
        if (!is_array($tmp)) {
            $tmp = func_get_args();
        }
        $has_jog = false;

        if (!array_key_exists('permissions', cache()->get(config('user_id')))) {
            self::cacheJog();
        }
        foreach ($tmp as $value) {
            if (cache()->get(config('user_id'))['permissions'][$value] === true) {
                $has_jog = true;
                break;
            }
        }
        return $has_jog;
    }

    static function checkJog($jog)
    {
        if ($jog === false || !call_user_func_array([self::class, 'getJog'], func_get_args())) {
            self::send_error("Nincs Jogosultság!", 403);
        }
    }

    static function cacheJog()
    {
        if (config('database.default') == "pgsql") {
            $permissionsTable = 'auth.permissions';
            $userPermissionsTable = 'auth.user_permissions';
        } else {
            $permissionsTable = 'permissions';
            $userPermissionsTable = 'user_permissions';
        }
        $permissions = DB::table($permissionsTable)->select('id', 'key')->get();
        $userPermissions = DB::table($userPermissionsTable)->where('user_id', config('user_id'))->get()->pluck('permission_id')->toArray();

        $cachePermissions = [];
        foreach ($permissions as $key => $value) {
            $cachePermissions[$value->key] = in_array($value->id, $userPermissions);
        }

        $cachedUser = cache()->get(config('user_id'));
        $cachedUser['permissions'] = $cachePermissions;
        cache()->put(config('user_id'), $cachedUser, config('app.CACHE_SECONDS'));
        return cache()->get(config('user_id'))['permissions'];
    }

    static function bexec($cmd, $do_in_background = true)
    {
        //háttérben futtatott console command
        $cmd = "php " . base_path() . DIRECTORY_SEPARATOR . "artisan " . $cmd;
        if (substr(php_uname(), 0, 7) == "Windows") {
            if ($do_in_background) {
                pclose(popen("start /B " . $cmd, "r"));
            } else {
                exec($cmd);
            }
        } else {
            if ($do_in_background) {
                exec($cmd . " > /dev/null &");
            } else {
                exec($cmd);
            }
        }
    }

    static function send_error($message, $code = 500, $title = false)
    {
        http_response_code($code);
        header("Access-Control-Allow-Origin: *");
        $out = ["message" => $message];
        if ($title) $out['title'] = $title;
        echo json_encode($out);
        exit();
    }

    static function logout($user)
    {
        $user->token()->revoke();
    }
}
