<?php
 
namespace mod\notificationcenter\commands;
use Illuminate\Console\Command;
use mod\notificationcenter\classes\PushReception as ClassesPushReception;

class PushReception extends Command
{

    protected $signature = 'PushReception {token} {title} {body} {entityId} {modul} {modulRecordId?} {priority?}';
 

    protected $description = 'Reception Push';
 

    public function handle(): void
    {
        $token = $this->argument('token');
        $token = "coU0q-DSQa6YTRsa8nIeKZ:APA91bFTzo3hqbS6rx30qaF3tEJSgDQRq7WNC4GAxlw-5fRcNVGAJIKq2Q-eakI3j4KyjQnhMcUhWwFPLW3OLDNo_bsYN6d5pNvOkJO_RLtZN-W_F_L6f5ELQ2RTnTqq3UXqmVblz2GN";
        ClassesPushReception::Push(
            $token,
            $this->argument('title'),
            $this->argument('body'),
            $this->argument('entityId'),
            $this->argument('modul'),
            $this->argument('modulRecordId'),
            $this->argument('priority')
        );
    }
}