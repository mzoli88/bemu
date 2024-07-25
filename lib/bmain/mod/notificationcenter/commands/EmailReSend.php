<?php
 
namespace mod\notificationcenter\commands;
use Illuminate\Console\Command;

use mod\notificationcenter\Jobs\SendEmail;
use mod\notificationcenter\models\EmailsRetries;

use Illuminate\Support\Facades\Bus;
use mod\notificationcenter\models\Emails;
use mod\notificationcenter\models\Priorities;
use Symfony\Component\Mime\Email;

class EmailReSend extends Command
{

    protected $signature = 'nc:EmailReSend';
 

    protected $description = 'Reception Email';
 

    public function handle(): void
    {
        EmailsRetries::where('attempt', '>=' , 10)->delete();

        $priorities = Priorities::getCache();
        EmailsRetries::where('attempt', '<', 10)->where('in_queue', 'N')->get()->each(function($emailRetry) use ($priorities) {
            $emailRetry->in_queue = 'I';

            $email = Emails::query()->find($emailRetry->notify_email_id);
            // dump($emailRetry);
            
            $Job = (new SendEmail($email->id))->onQueue("email_" .  strtolower($priorities[$email->priority]) . "_" . $email->entity_id) ;
            Bus::dispatch($Job);
            
            $emailRetry->save();
        });
    }
}

