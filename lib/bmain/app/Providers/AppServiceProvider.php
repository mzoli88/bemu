<?php

namespace App\Providers;

use App\Border3;
use App\Logging\CustomLoggerHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Border3::init();
        // echo (BORDER_PATH_BORDER);exit;

        // if (CustomLoggerHandler::canDebug()) {
        //     if (isset($_SERVER["REQUEST_URI"])) {
        //         //ne nyomja folyamatosan a lgokat debug esetén a refresh 
        //         if (preg_match('#auth/refresh#', $_SERVER["REQUEST_URI"])) return;
        //     }
        //     CustomLoggerHandler::request_log();
        //     CustomLoggerHandler::sql_log();
        //     CustomLoggerHandler::response_log();
        // }
    }
}
