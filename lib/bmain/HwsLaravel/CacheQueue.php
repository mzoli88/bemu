<?php

namespace hws;

use hws\xlsx\CacheTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class CacheQueue
{

    use CacheTrait;

    static function handle(callable $job)
    {

        if (request()->query('check_ready')) {
            return self::isReady();
        }
        if (request()->query('download')) {
            return self::download();
        }

        if (request()->query('stop')) {
            Cache::forget('workQuequeSignal_' . config('CacheQueue_id', 'u_') . config('user_id'));
            return self::isReady();
        }

        if (app()->runningInConsole()) {
            return $job();
        } else {
            if (!config('user_id')) return response()->json(['message' => 'Felhasználó nem beazonosítható!'], 500);
            $cache = self::getCache();
            Cache::put('workQuequeSignal_' . config('CacheQueue_id', 'u_') . config('user_id'), '(Előkészítés)', now()->addMinutes(5));
            if ($cache) return self::isReady();
            self::setCache([
                "ready" => false,
                "majax_type" => $_GET['majax_type'],
                "majax_store" => $_GET['majax_store'],
                "content" => null,
            ]);

            $file_path = '';
            $file_name = '';

            if (isset($_FILES['import_file'])) {
                $file_path = $_FILES['import_file']['tmp_name'] . '2';
                rename($_FILES['import_file']['tmp_name'], $file_path);
                $file_name = $_FILES['import_file']['name'];
            }

            self::bexec('callq ' . $_SERVER['REQUEST_METHOD'] . ' "' . preg_replace('/^.*\/api\//', 'api/', $_SERVER['REQUEST_URI']) . '" ' . config('user_id') . ' "' . $file_path . '"' . ' "' . $file_name . '"');

            return [
                "ready" => false,
                "majax_type" => $_GET['majax_type'],
                "majax_store" => $_GET['majax_store'],
                "content" => null,
            ];
        }
    }

    static function bexec($cmd, $do_in_background = true)
    {
        //háttérben futtatott console command
        $output = [];
        $cmd = "php " . base_path() . DIRECTORY_SEPARATOR . "artisan " . $cmd;
        if (substr(php_uname(), 0, 7) == "Windows") {
            if ($do_in_background) {
                pclose(popen("start /B " . $cmd, "r"));
            } else {
                exec($cmd, $output);
            }
        } else {
            if ($do_in_background) {
                exec($cmd . " > /dev/null &", $output);
            } else {
                exec($cmd, $output);
            }
        }
        return implode("\n", $output);
    }

    static function download()
    {
        $cache = self::getCache();
        if (!$cache) return ["ready" => true, "content" => null];

        if (is_array($cache['content']) && array_key_exists('file', $cache['content']) && Storage::exists($cache['content']['file'])) {
            self::delCache();
            Downloader::storage($cache['content']['file'], $cache['content']['name'], true);
        }
        self::delCache();
        return response()->json([
            "title" => 'A fájl már nem elérhető!',
            "message" => ' Ismételje meg a műveletet.',
        ], 500);
    }

    static function isReady()
    {
        $cache = self::getCache();

        if (!$cache) return ["ready" => true, "content" => null];

        if (!is_array($cache) || !array_key_exists('ready', $cache) || $cache['ready'] != true) {
            $signal = Cache::has('workQuequeSignal_' . config('CacheQueue_id', 'u_') . config('user_id'));

            if (!$signal) {
                $cache['ready'] = true;
                $cache['majax_type'] = $cache['majax_type'];
                $cache['majax_store'] = $cache['majax_store'];
                $cache['content'] = 'error';
                $cache['signal'] = 'error';
                self::delCache();
            } else {
                $cache['signal'] = Cache::get('workQuequeSignal_' . config('CacheQueue_id', 'u_') . config('user_id'));
            }
            return $cache;
        }


        if (is_array($cache['content']) && array_key_exists('file', $cache['content'])) {
            unset($cache['content']['file']);
            $cache['content']['download'] = true;
        } else {
            self::delCache();
        }

        return $cache;
    }

    static function getCache()
    {
        return Cache::get('workQueque_' . config('CacheQueue_id', 'u_') . config('user_id'));
    }

    static function setCache($cache)
    {
        Cache::put('workQueque_' . config('CacheQueue_id', 'u_') . config('user_id'), $cache);
    }

    static function delCache()
    {
        Cache::forget('workQueque_' . config('CacheQueue_id', 'u_') . config('user_id'));
    }

    static function setCommand()
    {
        Artisan::command('call {method} {route} {user_id?}', function ($method, $route, $user_id = null) {
            parse_str(preg_replace('/.*\?/', '', $route), $_GET);
            Config::set('user_id', $user_id);

            $request = Request::create($route, $method);
            $response = app()->handle($request);

            if ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\JsonResponse) {
                dd($response->getOriginalContent());
            } else {
                dd($response);
            }
        })->describe('Console-ba futtatható controller');

        Artisan::command('callq {method} {route} {user_id} {import_file_path?} {file_name?}', function ($method, $route, $user_id, $import_file_path = null, $file_name = null) {
            parse_str(preg_replace('/.*\?/', '', $route), $_GET);
            Config::set('user_id', $user_id);
            Config::set('callq', true);

            if (!empty($import_file_path)) {
                $_FILES = [
                    'import_file' => [
                        'tmp_name' => $import_file_path,
                        'name' => $file_name
                    ]
                ];
            }
            try {
                $request = Request::create($route, $method);
                $content = app()->handle($request);
                if ($content instanceof \Illuminate\Http\Response || $content instanceof \Illuminate\Http\JsonResponse) {
                    $content = $content->getOriginalContent();
                }
                logger()->debug("workQuequeRun output:" . print_r($content, true));
            } catch (\Throwable $th) {
                $content = 'error';
                report($th);
            }
            $cache = CacheQueue::getCache();
            $cache["ready"] = true;
            $cache["content"] = $content;
            CacheQueue::setCache($cache);
            if (!empty($import_file_path)) {
                unlink($import_file_path);
            }
            // logger()->debug('CacheQueque End');
        })->describe('Console-ba futtatható controller');
    }
}
