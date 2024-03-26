<?php

namespace hws;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class CacheQueue
{

    // use CacheTrait;

    static function handle(callable $job, $eventName = "Exportálás")
    {

        if (app()->runningInConsole()) {
            return $job();
        } else {
            if (!getUser()) return response()->json(['message' => 'Felhasználó nem beazonosítható!'], 500);

            $cache = self::getCache();
            if ($cache) return self::isReady();

            // Cache::put('workQuequeSignal_u_' . getUserId(), '(Előkészítés)', now()->addMinutes(5));

            self::setCache([
                "Queque" => true,
                "ready" => false,
                "name" => $eventName,
            ]);

            $file_path = '';
            $file_name = '';

            if (isset($_FILES['import_file'])) {
                $file_path = $_FILES['import_file']['tmp_name'] . '2';
                rename($_FILES['import_file']['tmp_name'], $file_path);
                $file_name = $_FILES['import_file']['name'];
            }

            self::bexec('callq ' . $_SERVER['REQUEST_METHOD'] . ' "' . preg_replace('/^.*\/remote\//', '/', $_SERVER['REQUEST_URI']) . '" ' . getUserId() . ' ' . getEntity() . ' "' . $file_path . '"' . ' "' . $file_name . '"');

            return [
                "Queque" => true,
                "ready" => false,
                "name" => $eventName,
            ];
        }
    }

    static function killp($pid){
        if (substr(php_uname(), 0, 7) == "Windows") {
            exec("taskkill /PID $pid /F", $output);
        } else {
            exec("kill $pid", $output);
        }
    }

    static function bexec($cmd, $do_in_background = true)
    {
        //háttérben futtatott console command
        $output = [];

        $php = 'php' . preg_replace('#^([1-9]*)\.([1-9]*)\..*#', '$1.$2', phpversion());
        if (shell_exec($php . ' -v') == null) $php = "php";

        $cmd = $php . " " . base_path() . DIRECTORY_SEPARATOR . "artisan " . $cmd;
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
        if (!$cache) return ["Queque" => true, "ready" => true, "content" => null];

        $path = BORDER_PATH_BORDERDOC . request()->get('modul_azon');
        Config::set('filesystems.disks.local.root', $path);
        Config::set('path.storage', $path);

        if (array_key_exists('content',$cache) && array_key_exists('file', $cache['content']) && Storage::exists($cache['content']['file'])) {
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

        if(!array_key_exists("Queque",$cache)) $cache['Queque'] = true;

        if (request()->query('stopQueque') == true) {
            if(array_key_exists('pid',$cache)) CacheQueue::killp($cache['pid'],0);
            CacheQueue::delCache();
            return ["Queque" => true, "ready" => true , 'info' => 'manual stop finish'];
        }

        if (!$cache) return ["Queque" => true, "ready" => true];
        // if ($cache['ready'] == true) self::delCache();
        return $cache;
    }

    static function getCache()
    {
        return Cache::get('workQueque_u_' . getUserId());
    }

    static function setCache($cache)
    {
        Cache::put('workQueque_u_' . getUserId(), $cache);
    }

    static function delCache()
    {
        Cache::forget('workQueque_u_' . getUserId());
    }
}
