<?php
 
namespace mod\notificationcenter\commands;
use Illuminate\Console\Command;

use mod\notificationcenter\models\Emails;

class EmailMigrate extends Command
{

    protected $signature = 'notify:EmailMigrate {chunk} {dryrun?}';
 

    protected $description = 'Reception Email';
 

    public function handle(): void
    {
        $generalParams = getParams('notificationcenter');

        $generalParams['notification_email_path'] = "app/mail/body/";
        $dryrun = $this->argument('dryrun');
        $chunk = $this->argument('chunk');

        $path = storage_path() . '/' . $generalParams['notification_email_path'];

        $db = 0;

        // $emails = Emails::where('body', '!=', '')
        $emails = Emails::where('created_at', '>=', now()->subDays(90))
                        ->chunkById($chunk, function($emails) use ($path, $dryrun, &$db) {
                            foreach($emails as $email){

                                $db++;
                                $entityId = $email->entity_id;
    
                                if (!is_dir($path . $entityId . '/new')){
                                    mkdir($path . $entityId . '/new', 0777, true);
                                    mkdir($path . $entityId . '/error', 0777, true);
                                    mkdir($path . $entityId . '/sended', 0777, true);
                                }
                        

                                switch ($email->state) {
                                    case 1:
                                        $dir = '/new/';
                                        break;
                                    case 2:
                                        $dir = '/error/';
                                        break;
                                    case 3:
                                        $dir = '/sended/';
                                        break;
                                    default:
                                        $dir = '/new/';
                                        break;
                                }

                                if (strlen($email->body) < 1){
                                    continue;
                                }
                                
                                // $zipFileName = $path . '/' . $entityId . '/new/' . $email->id . '.zip';
                                $zipFileName = $path . $entityId . $dir . $email->id . '.zip';
    

                                $body = $email->body;
                        
                                $this->info('Email tartalom mentése: ' . $zipFileName);
                        
                                $zip = new \ZipArchive();
                        
                                if ($zip->open($zipFileName, \ZipArchive::CREATE) === TRUE) {
                                    $zip->addFromString('body.txt', $body);
                                    $zip->close();

                                    if ($dryrun){

                                        if ($db % 100 == 0) {
                                            $this->info('Aktuális darabszám (arhíválás): ' . $db);
                                            $this->info('Aktuális dátum (arhíválás): ' . $email->created_at);
                                        }

                                        continue;
                                    }
                                    $email->body = '';
                                    $email->save();

                                    if ($db % 100 == 0) {
                                        $this->info('Aktuális darabszám (arhíválás): ' . $db);
                                        $this->info('Aktuális dátum (arhíválás): ' . $email->created_at);
                                    }
                                } else {
                                    hwslog(event:'Email küldés', message:'Siketelen email tartalom mentés' , err:"Siketelen email tartalom mentés (".$email->id.")", modul_azon:'notificationcenter');
                                }

                                // die();
                            }

                        });

        $db = 0;

        $emails = Emails::where('created_at', '<', now()->subDays(90))
        // $emails = Emails::where('body', '!=', '')
                        ->chunkById($chunk, function($emails)  use ($dryrun, &$db){
                            foreach($emails as $email){
                                $db++;
                                if ($dryrun){
                                    if ($db % 50 == 0) {
                                        $this->info('Aktuális darabszám (törlés): ' . $db);
                                        $this->info('Aktuális dátum (törlés): ' . $email->created_at);
                                    }
                                    continue;
                                }

                                if (strlen($email->body)){
                                    $email->body = '';
                                    $email->save();
                                }

                                if ($db % 50 == 0) {
                                    $this->info('Aktuális darabszám (törlés): ' . $db);
                                    $this->info('Aktuális dátum (törlés): ' . $email->created_at);
                                }
                            }
                        });
    

    }
}

