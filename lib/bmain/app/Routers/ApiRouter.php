<?php

namespace App\Routers;

use Illuminate\Support\Facades\Route;

class ApiRouter
{

    static function route($dir, $postfix = 'RMC')
    {
        if (is_dir($dir)) self::getDirControllers($dir, $postfix);
    }

    static function getGroup(&$group_prefix)
    {
        $groupStacks =  Route::getGroupStack();
        $lastGroupStacks = end($groupStacks);
        $group_prefix = $lastGroupStacks['prefix'];
    }

    static function getDirControllers($dir, $postfix)
    {
        $files = scandir($dir);
        foreach ($files as $value) {
            if ($value == "." || $value == "..") continue;
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (is_dir($path)) {
                Route::prefix($value)->namespace($value)->group(function () use ($path, $postfix) {
                    self::getDirControllers($path, $postfix);
                });
            } else {
                // dd($value, $path);
                if (preg_match('/' . $postfix . '\.php$/', $value)) self::MakeRoute($value, $path, $postfix);
            }
        }
    }

    static function MakeRoute($file, $path, $postfix)
    {
        try {
            
            // self::getGroup($group_prefix);
            // dd($path, base_path());
            $class = preg_replace('/\.php$/', '', $path);
            $class = str_replace(base_path(), '', $class);
            // $class = preg_replace('/^.*mod/', '', $path);
            $class = str_replace('/', '\\', $class);
            $class = str_replace('/', '\\', $class);
            
            $uri = strtolower(preg_replace('/' . $postfix . '\.php$/', '', $file));
            $ct = $class;
            // if($postfix == 'IF')dd($uri, $ct,$class,method_exists($class, 'list'));
            // $class::checkClassName($class); //TODO

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
                // Route::get($uri . '/list', $ct . '@list');
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
        } catch (\Throwable $th) {
            report($th);
        }
    }
}
