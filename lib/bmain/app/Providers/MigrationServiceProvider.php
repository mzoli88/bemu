<?php

namespace App\Providers;

use App\Console\MigrateCommand as MigrateCommandNew;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\MigrationServiceProvider as ServiceProvider;


class MigrationServiceProvider extends ServiceProvider
{


    protected function registerMigrateCommand()
    {
        $this->app->singleton(MigrateCommand::class, function ($app) {
            return new MigrateCommandNew($app['migrator'], $app[Dispatcher::class]);
        });
    }
}
