<?php

namespace App\Console;

use Illuminate\Database\Console\Migrations\MigrateCommand as BaseCommand;

class MigrateCommand extends BaseCommand
{

    protected function getMigrationPaths()
    {
        return array_merge(
            parent::getMigrationPaths(),
            collect(config('mods'))->filter(function ($data, $modul_azon) {
                return is_dir($data['dir'] . 'migrations');
            })->map(function ($data) {
                return $data['dir'] . 'migrations';
            })->values()->toArray()
        );
    }
}
