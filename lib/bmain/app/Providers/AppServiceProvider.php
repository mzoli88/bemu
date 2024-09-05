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

        if (CustomLoggerHandler::canDebug()) {
            \Illuminate\Support\Facades\Config::set('app.debug',true);
            CustomLoggerHandler::request_log();
            CustomLoggerHandler::sql_log();
            CustomLoggerHandler::response_log();
        }
    }
}
