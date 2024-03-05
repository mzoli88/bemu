<?php

namespace mod\admin\commands;

use Illuminate\Console\Command;


class ClearCacheVue extends Command
{

    protected $signature = 'clearCacheVue';


    protected $description = 'Clear vue cache';


    public function handle(): void
    {
        if (!file_exists(base_path('public/app/config.json'))) {
            file_put_contents(base_path('public/app/config.json'), json_encode([
                "name" => 'Border',
            ]));
        }

        $dir = realpath(__DIR__ . '/../../storage/vue');
        if (!$dir) return;
        $files = scandir($dir);
        foreach ($files as $file) {
            if (preg_match('/\.json$/', $file)) unlink($dir . DIRECTORY_SEPARATOR . $file);
        }
        $this->info('Vue cache cleared successfuly! ' . $dir);
    }
}
