<?php

namespace mod\admin\commands;

use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;


class DeletempFiles extends Command
{

    protected $signature = 'DeletempFiles';


    protected $description = 'Nem törölt tmp fájlok megszüntetése';


    public function handle(): void
    {
        collect(Storage::listContents('tmp'))->each(function ($file) {
            if ($file->lastModified() < now()->subDays(30)->getTimestamp()) {
                if ($file->type() == 'dir') {
                    Storage::deleteDirectory($file->path());
                } else {
                    Storage::delete($file->path());
                }
            }
        });
    }
}
