<?php
 
namespace mod\notificationcenter\commands;
use Illuminate\Console\Command;
use mod\notificationcenter\models\Notifications;

class NotificationReception extends Command
{

    protected $signature = 'NotificationReception {type} {to_user_id} {from_user_id} {message} {modul_azon?}';
 

    protected $description = 'Reception Notification';
 

    public function handle(): void
    {
        //addNotification($type, $to_user_id, $message = null, $link = null /* Csak hash, href="#start/0" */, $modul_azon = null, $duplicate_delete = true)
        Notifications::addNotification(
            $this->argument('type'),
            $this->argument('to_user_id'),
            null,
            $this->argument('from_user_id'),
            $this->argument('message'),
            $this->argument('modul_azon')
        );
        logger()->info('NotificationReception');
    }
}