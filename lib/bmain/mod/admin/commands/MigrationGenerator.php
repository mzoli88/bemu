<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;
use hws\generator\MigrationGenerator as MG;

class MigrationGenerator extends Command
{

    protected $signature = 'hws:mig {modul_azon} {table?}';


    protected $description = 'Generate model';


    public function handle()
    {
        $modul_azon = $this->argument('modul_azon');
        $table = $this->argument('table');
        $prefix = config('mods.' . $modul_azon . '.db_prefix', [$modul_azon . '_']);
        MG::$table_prefix = $prefix;
        MG::$base_file_path = config('mods.' . $modul_azon . '.dir') . 'migrations';
        MG::$modul_azon = $modul_azon;
        new MG($table);
    }
}
