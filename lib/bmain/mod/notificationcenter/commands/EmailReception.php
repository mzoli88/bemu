<?php
 
namespace mod\notificationcenter\commands;
use Illuminate\Console\Command;

use mod\notificationcenter\classes\EmailReception as eReception;

class EmailReception extends Command
{

    protected $signature = 'EmailReception {subject} {addressee} {modul} {modulRecordId?} {body?} {priority?} {entity?} {cc?} {bcc?}';
 

    protected $description = 'Reception Email';
 

    public function handle(): void
    {
        // sleep(10);
        eReception::Email(
            subject: $this->argument('subject'), 
            body: $this->argument('body'), 
            addressee: $this->argument('addressee'), 
            modul: $this->argument('modul'), 
            modulRecordId: $this->argument('modulRecordId'), 
            priority: $this->argument('priority'), 
            entityId: $this->argument('entity'),
            cc_emails: $this->argument('cc'),
            bcc_emails: $this->argument('bcc'),
        );
        logger()->info('EmailReception');
    }
}

