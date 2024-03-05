<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;
use hws\generator\ModelGenerator as MG;

class ModelGenerator extends Command
{

    protected $signature = 'hws:m {modul_azon} {name?}';


    protected $description = 'Generate model';


    public function handle()
    {
        $modul_azon = $this->argument('modul_azon');
        $name = $this->argument('name');
        $prefix = config('mods.' . $modul_azon . '.db_prefix', [$modul_azon . '_']);
        MG::$table_prefix = $prefix;
        MG::$modul_azon = $modul_azon;
        MG::$file_path = config('mods.' . $modul_azon . '.dir') . 'models';
        MG::$name_space = 'mod\\' . $modul_azon . '\\models';
        new MG($name);
    }
}
