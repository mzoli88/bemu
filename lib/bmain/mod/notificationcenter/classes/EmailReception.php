<?php

namespace mod\notificationcenter\classes;

use mod\notificationcenter\models\Emails;
use mod\notificationcenter\models\Priorities;
use mod\notificationcenter\Jobs\SendEmail;

use PHPMailer\PHPMailer\PHPMailer;

use Illuminate\Support\Facades\Bus;

use Exception as GlobalException;
use mod\notificationcenter\models\DailyEmails;
use mod\notificationcenter\models\EmailAttachments;

use Illuminate\Support\Facades\DB;
use mod\notificationcenter\models\QueuesCount;

class EmailReception {
    
    protected static $use_keys = []; 

    protected static function EntityParams($use_keys, $entityId)
    {
        $params = getParams('notificationcenter');

        $entityParams = [];

        $keys = [];
        foreach($use_keys as $value){
            $keys[] = $value . $entityId;
        }
        foreach($params as $key =>  $val){
            if (in_array($key, $keys)){
                $_key = explode('_', $key);
                unset($_key[count( $_key) -1 ]);
                $entityParams[implode('_', $_key) . '_'] = $val;
            }
        }

        return $entityParams;
    }
    
    public static function HasEmailEntityParams($entityId)
    {
        self::$use_keys['email']  = [
            "smtp_host_",
            "smtp_port_",
            "smtp_username_",
            "smtp_password_",
            "smtp_security_",
            "smtp_sender_name_",
            "smtp_sender_email_",
            "smtp_auth_",
            "smtp_authtype_"
        ];

        $entityParams = self::EntityParams(self::$use_keys['email'], $entityId);

        if (empty($entityParams)){
            sendError("Értesítés központban hiányzó entitás paraméterek!");
            return false;
        }

        return $entityParams;
    }

    /**
     * Emails
     * 
     *   $records['subject'], 
     *   $records['body'], 
     *   $records['to'], 
     *   $records['modul'], 
     *   $records['modulRecordId'] ?? null, 
     *   $records['priority'] ?? 1,  
     *   $records['entityId'] ?? null, 
     *   $records['isHtml']  ?? true, 
     *   $records['sender']  ?? [] 
     *
     * @param  array $records 
     * @return void
     */
    public static function Emails(array $records)
    {
        $out = [];
        foreach($records as $key => $row){
            $out[] = self::Email(
                $row['subject'], 
                $row['body'], 
                $row['to'], 
                $row['modul'], 
                $row['modulRecordId'] ?? null, 
                $row['priority'] ?? 1,  
                $row['entityId'] ?? null, 
                $row['isHtml']  ?? true, 
                $row['sender']  ?? [] 
            );
        }
        return $out;
    }
    
    /**
     * Email
     *
     * @param  mixed $subject levél tárgya
     * @param  mixed $body levél tartalma
     * @param  mixed $addressee címzett
     * @param  mixed $modul modul azonosító
     * @param  mixed $modulRecordId küldő modul rekord azonosító
     * @param  mixed $priority prioritás
     * @param  mixed $entityId entiás azonosító
     * @param  mixed $attachements csatolás
     * @param  mixed $isHtml html-e a tartalom
     * @param  mixed $sender küldő
     */
    public static function Email(
        $subject, 
        $body, 
        $addressee, 
        $modul, 
        $modulRecordId = null, 
        $priority = 1,  
        $entityId = null, 
        $attachements = [], 
        $isHtml = true, 
        $sender = [],
        $callback = null,
        $cc_emails = null,
        $bcc_emails = null
        )
    {
        if (empty( $entityId)){
            $entityId = getEntity();
        }
        $entityParams = self::HasEmailEntityParams($entityId);

        if (!Priorities::query()->where('id', $priority)->exists()){
            sendError("Hibás prioritás!");
        }

        if (!empty($sender)){
            $entityParams['smtp_sender_name_'] = $sender['name'];
            $entityParams['smtp_sender_email_'] = $sender['email'];
        }



        $id = null;

        $id = self::saveEmail($modul,
            $entityId,
            $priority,
            $subject,
            $body,
            $isHtml,
            $addressee,
            $entityParams,
            $modulRecordId,
            $attachements,
            $callback,
            $cc_emails,
            $bcc_emails


        );

        return $id;
    }
    
    /**
     * saveEmail
     *
     * @param  mixed $modul modul azonosító
     * @param  mixed $entityId entiás azonosító
     * @param  mixed $priority prioritás
     * @param  mixed $subject tárgy
     * @param  mixed $body levél tartalma
     * @param  mixed $isHtml html-e a tartalom
     * @param  mixed $addressee címzett
     * @param  mixed $entityParams entitás paraméterek
     * @param  mixed $modulRecordId küldő modul rekord azonosító
     * @param  mixed $attachements csatolás
     * @return id
     */
    protected static function saveEmail($modul,
        $entityId,
        $priority,
        $subject,
        $body,
        $isHtml,
        $addressee,
        $entityParams,
        $modulRecordId,
        $attachements,
        $callback,
        $cc_emails,
        $bcc_emails
    )
    {
        $email = new Emails;

        $email->entity_id = $entityId;
        $email->priority = $priority;
        $email->state = 1;
        $email->subject = $subject;
        // $email->body = $body;
        $email->modul_id = $modul;
        $email->is_html = $isHtml;
        $email->addressee = $addressee;
        $email->modul_record_id = $modulRecordId;
        $email->callback = $callback;

        $email->cc_emails = $cc_emails;
        $email->bcc_emails = $bcc_emails;

        if ($entityParams['smtp_sender_email_']){
            $email->sender_name = $entityParams['smtp_sender_name_'];
            $email->sender_email_address = $entityParams['smtp_sender_email_'];
        }

        $email->sender_params = json_encode($entityParams);

        $priorities = Priorities::getCache();

        if ($email->save()){

            $generalParams = getParams('notificationcenter');
            $path = storage_path() . '/' . $generalParams['notification_email_path'];
            //db helyett állományba mentés
            if (!is_dir($path . $entityId . '/new')){
                mkdir($path . $entityId . '/new', 0777, true);
                mkdir($path . $entityId . '/error', 0777, true);
                mkdir($path . $entityId . '/sended', 0777, true);
            }



            $zipFileName = $path . $entityId . '/new/' . $email->id . '.zip';

            //echo $zipFileName;

            $zip = new \ZipArchive();

            if ($zip->open($zipFileName, \ZipArchive::CREATE) === TRUE) {
                $zip->addFromString('body.txt', $body);
                $zip->close();
            } else {
                hwslog(event:'Email küldés', message:'Siketelen email tartalom mentés' , err:"Siketelen email tartalom mentés (".$email->id.")", modul_azon:'notificationcenter');
            }

            $dailyMail = DailyEmails::updateOrInsert(
                [
                    'day' => date('Y-m-d'),
                    'priority' => $priority,
                    'entity_id' => $entityId,
                    
                ],
                [
                    'day' => date('Y-m-d'),
                    'to_out' => 1,
                    'priority' => $priority,
                    'entity_id' => $entityId,
                ]
            );
            
            QueuesCount::updateOrInsert(
                [
                    'priority' => $priority,
                    'entity_id' => $entityId,
                    'type' => "email",
                ],
                [
                    'priority' => $priority,
                    'entity_id' => $entityId,
                    'type' => "email",
                    'count' => DB::raw('count + 1')
                ]
            );

            if (!empty($attachements)) {
                foreach ($attachements as $attachement) {
                    $emailAttachment = new EmailAttachments();

                    $emailAttachment->filename = $attachement['filename'];
                    $emailAttachment->filep_path = $attachement['file_path'];
                    $emailAttachment->notify_email_id = $email->id;

                    $emailAttachment->save();
                }
            }

            $Job = (new SendEmail($email->id))->onQueue("email_" .  strtolower($priorities[$priority]) . "_" . $entityId) ;
            Bus::dispatch($Job);
            return  $email->id;
        }else{
            sendError("Email mentés sikertelen!");
        }
    }

    public static function sendMail($id)
    {
        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions
        try {

            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->SMTPDebug = 0;
            //$mail->isSMTP();
            $mail->Host = 'localhost';             //  smtp host
            $mail->SMTPAuth = false;
            $mail->Port = 25;                          // port - 587/465

            $mail->setFrom('panik.zoltan@hwstudio.hu', 'Panik HW');
            $mail->addAddress("panik.zoltan@gmail.com");
            // $mail->addCC($request->emailCc);
            // $mail->addBCC($request->emailBcc);
            // $mail->addReplyTo('sender@example.com', 'SenderReplyName');

            // if(isset($_FILES['emailAttachments'])) {
            //     for ($i=0; $i < count($_FILES['emailAttachments']['tmp_name']); $i++) {
            //         $mail->addAttachment($_FILES['emailAttachments']['tmp_name'][$i], $_FILES['emailAttachments']['name'][$i]);
            //     }
            // }

            $mail->isHTML(true);                // Set email content format to HTML
            $mail->Subject = "Teszt öüóőúéá";
            $mail->Body    = "Body";

            // $mail->AltBody = plain text version of email body;

            if( !$mail->send() ) {
                echo $mail->ErrorInfo;
                hwslog(event:'Email küldés', message:'Siketelen várakozási sorba rögzítés' , err:$mail->ErrorInfo, modul_azon:'notificationcenter');
            }
            else {
                echo "send success";
                hwslog(event:'Email küldés', message:'Sikeres várakozási sorba rögzítés', modul_azon:'notificationcenter');
            }
        } catch (GlobalException $e) {
            echo "send global error" . $e->getMessage();
        }
    }

}