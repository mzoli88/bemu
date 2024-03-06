<?php

namespace hws\rmc;

use Illuminate\Support\Facades\Route;

class Router
{

    static $controller_ending = 'RMC'; //'Controller';
    // php artisan optimize
    // php artisan optimize:clear
    // php artisan route:list

    static function route($first = true)
    {

        self::getGroup($group_prefix, $group_namespace);

        $group_prefix = str_replace('/', '\\', $group_prefix);
        if (!empty($group_prefix) && !preg_match('/' . str_replace('\\', '\\\\', $group_prefix) . '$/', $group_namespace)) {
            if ($first == false) return;
            Route::namespace($group_prefix)->group(function () {
                self::route(false);
            });
            return;
        }

        $path = realpath(app_path() . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $group_prefix));
        self::getDirControllers($path);
    }

    static function getGroup(&$group_prefix, &$group_namespace)
    {
        $groupStacks =  Route::getGroupStack();
        $lastGroupStacks = end($groupStacks);
        $group_namespace = $lastGroupStacks['namespace'];
        $group_prefix = $lastGroupStacks['prefix'];
    }

    static function getDirControllers($dir)
    {
        $files = scandir($dir);
        foreach ($files as $value) {
            if ($value == "." || $value == "..") continue;
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (is_dir($path)) {
                Route::prefix($value)->namespace($value)->group(function () use ($path) {
                    self::getDirControllers($path);
                });
            } else {
                if (preg_match('/RMC\.php$/', $value)) self::MakeRoute($value, $path);
            }
        }
    }

    static function MakeRoute($file, $path)
    {
        self::getGroup($group_prefix, $group_namespace);
        $ct = preg_replace('/\.php$/', '', $file);
        $class = str_replace('/', '\\', '\\' . $group_namespace . '\\' . $ct);

        $uri = strtolower(preg_replace('/RMC\.php$/', '', $file));

        $class::checkClassName($class);

        if (method_exists($class, 'export')) Route::any($uri . '/export', $ct . '@export');
        if (method_exists($class, 'import')) Route::any($uri . '/import', $ct . '@import');
        if (method_exists($class, 'csv')) Route::any($uri . '/csv', $ct . '@csv');
        if (method_exists($class, 'pdf')) Route::any($uri . '/pdf', $ct . '@pdf');
        if (method_exists($class, 'xls')) Route::any($uri . '/xls', $ct . '@xls');
        if (method_exists($class, 'download')) Route::any($uri . '/download', $ct . '@download');
        if (method_exists($class, 'download')) Route::any($uri . '/{id}/download', $ct . '@download');
        if (method_exists($class, 'download')) Route::any($uri . '/download/{id}', $ct . '@download');

        if (method_exists($class, 'view')) {
            Route::get($uri . '/view/{id}', $ct . '@view');
            Route::get($uri . '/{id}', $ct . '@view');
        }
        if (method_exists($class, 'list')) {
            Route::get($uri . '/list', $ct . '@list');
            Route::get($uri, $ct . '@list');
            if (!method_exists($class, 'view')) {
                Route::get($uri . '/{id}', $ct . '@list');
            }
        }
        if (method_exists($class, 'update')) {
            Route::put($uri . '/update/{id}', $ct . '@update');
            Route::put($uri, $ct . '@update');
            Route::put($uri . '/{id}', $ct . '@update');
        }
        if (method_exists($class, 'delete')) {
            Route::delete($uri . '/delete/{id}', $ct . '@delete');
            Route::delete($uri, $ct . '@delete');
            Route::delete($uri . '/{id}', $ct . '@delete');
        }
        if (method_exists($class, 'create')) {
            Route::post($uri . '/create', $ct . '@create');
            Route::post($uri, $ct . '@create');
            Route::post($uri . '/{id}', $ct . '@create');
        }
    }
}
