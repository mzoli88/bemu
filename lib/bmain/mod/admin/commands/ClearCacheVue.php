<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;


class ClearCacheVue extends Command
{

    protected $signature = 'clearCacheVue';


    protected $description = 'Clear vue cache';


    public function handle(): void
    {
        $dir = storage_path('vue');
        $files = scandir($dir);
        foreach ($files as $file) {
            if (preg_match('/\.json$/', $file)) unlink($dir . DIRECTORY_SEPARATOR . $file);
        }
        $this->info('Vue cache cleared successfuly! ' . $dir);
    }
}
