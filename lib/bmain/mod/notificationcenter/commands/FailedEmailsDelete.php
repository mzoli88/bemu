<?php

namespace mod\notificationcenter\commands;

use Illuminate\Console\Command;
use mod\notificationcenter\models\Emails;

class FailedEmailsDelete extends Command
{

    protected $signature = 'nc:FailedEmailsDelete';


    protected $description = 'failed Emails Delete';

    protected $outOfDate = 91;


    public function handle(): void
    {
        $entities = entities();
        $generalParams = getParams('notificationcenter');

        foreach ($entities as $entity) {
            if ($entity['status'] == 'N') {
                continue;
            }

            $entityId = $entity['id'];
            $directory = storage_path() . '/' . $generalParams['notification_email_path'] . $entityId . '/error/';
            if (!is_dir($directory)) {
                continue;
            }

            $emails = Emails::whereDate('created_at', '=', now()->subDays($this->outOfDate))
                ->where('state', 2) // Sikertelen
                ->where('entity_id', $entityId)
                ->chunk(10, function ($emails) use ($directory) {
                    foreach ($emails as $email) {
                        $filePath = $directory . $email->id . '.zip';
                        if (is_file($filePath) and unlink($filePath)) {
                            $this->info("Deleted: " . basename($filePath));
                        } else {
                            $this->error("Could not delete: " . basename($filePath));
                        }
                        // die();
                    }
                });
        }
    }
}
