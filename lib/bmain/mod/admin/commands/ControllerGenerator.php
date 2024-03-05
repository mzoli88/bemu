<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;
use hws\generator\ControllerGenerator as CG;

class ControllerGenerator extends Command
{

    protected $signature = 'hws:c {modul_azon} {name} {methods?*}';


    protected $description = 'Generate model';


    public function handle()
    {
        $modul_azon = $this->argument('modul_azon');
        $name = $this->argument('name');
        $methods = $this->argument('methods');

        CG::$file_path = config('mods.' . $modul_azon . '.dir') . 'controllers';
        CG::$name_space = 'mod\\' . $modul_azon . '\\controllers';
        CG::$modul_azon = $modul_azon;
        new CG($name,$methods);
    }
}
