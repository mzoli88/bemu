<?php

namespace mod\notificationcenter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;

use Exception as GlobalException;
use mod\notificationcenter\models\DailyEmails;
use mod\notificationcenter\models\EmailAttachments;
use mod\notificationcenter\models\Emails;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email = null;

    public $tries = 10;

    public $timeout = 60;

    public $zipFileName;

    public $generalParams;

    /**
     * Create a new job instance.
     */
    public function __construct($email)
    {
        // Logger()->info('__construct: ' . $email);
        $this->email = $email;

        // dump($this);
    }


    public function getImagesFromMsg(&$msg)
    {
        $arrSrc = array();
        if (!empty($msg)) {
            preg_match_all('/<img[^>]+>/i', stripcslashes($msg), $imgTags);

            $elemets = [];
            //All img tags
            for ($i = 0; $i < count($imgTags[0]); $i++) {
                preg_match('/src="([^"]+)/i', $imgTags[0][$i], $withSrc);
                //Remove src
                $withoutSrc = str_ireplace('src="', '', $withSrc[0]);

                $elemets[] = $withoutSrc;
            }

            $elemets = array_unique($elemets);

            foreach ($elemets as $i => $withoutSrc) {
                //data:image/png;base64,
                if (strpos($withoutSrc, ";base64,")) {
                    $msg = str_replace($withoutSrc, 'cid:imageid_' . $i, $msg);
                    //data:image/png;base64,.....
                    list($type, $data) = explode(";base64,", $withoutSrc);
                    $arrSrc['imageid_' . $i] = [
                        'content' => base64_decode($data),
                        'type' => str_replace('data:', '', $type),
                    ];
                }
            }
        }
        return $arrSrc;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // dump($this);
        // Process uploaded podcast...
        Logger()->info('handle: ' . $this->email);

        // echo $this->job->getJobId() . "\n";

        $model = Emails::query()->find((int)$this->email);

        Logger()->info('email küldés model: ' . var_export($model, true));

        if ($model) {

            $generalParams = getParams('notificationcenter');
            $this->generalParams = $generalParams;

            $entityId = $model->entity_id;
            $this->zipFileName = storage_path() . "/" . $generalParams['notification_email_path'] . $entityId . '/new/' . $model->id . '.zip';

            $params = json_decode($model->sender_params, true);

            require base_path("vendor/autoload.php");
            $mail = new PHPMailer(true);     // Passing `true` enables exceptions
            try {
                logger()->info("email PARAMS: " . var_export($params, true));

                $mail->CharSet = PHPMailer::CHARSET_UTF8;

                $mail->Timeout = 45; //így egy levél regrosszabb esetben ~45 percig próbálkozik

                if ($model->is_html) {

                    $mail->isHTML(true);                // Set email content format to HTML
                }

                $mail->isSMTP();

                $mail->SMTPDebug = 0;
                $mail->Host = $params['smtp_host_'];
                $mail->Port = $params['smtp_port_'];

                $mail->SMTPAuth = $params['smtp_auth_'] == 'I' ? true : false;

                $mail->SMTPAutoTLS = $params['smtp_security_'] == 'I';

                if (!$mail->SMTPAutoTLS) {
                    $mail->SMTPSecure = false;
                }

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                if ($mail->SMTPAuth) {
                    $mail->AuthType = $params['smtp_authtype_'];         // Auth type PLAIN, LOGIN, NTLM, CRAM-MD5, XOAUTH2
                    $mail->Username = $params['smtp_username_'];          // SMTP username
                    $mail->Password = $params['smtp_password_'];
                }

                $mail->setFrom($params['smtp_sender_email_'], $params['smtp_sender_name_']);

                $mail->AddAddress(trim($model->addressee));

                if (!empty($model->cc_emails)){
                    $cc_emails = explode(';', $model->cc_emails);
                    foreach ($cc_emails as $cc) {
                        $mail->addCC($cc);
                    }
                }

                if (!empty($model->bcc_emails)){
                    $bcc_emails = explode(';', $model->bcc_emails);
                    foreach ($bcc_emails as $bcc) {
                        $mail->addBCC($bcc);
                    }
                }

                $emailAttachments = EmailAttachments::query()->where('notify_email_id', $model->id)->get();

                if (!empty($emailAttachments)) {
                    foreach ($emailAttachments as $key => $emailAttachment) {
                        $mail->addAttachment($emailAttachment->filep_path, $emailAttachment->filename);
                    }
                }

                $mail->Subject = $model->subject;

                if (!is_file($this->zipFileName)){
                    throw new \Exception('Nem található az email szövegét tartlamazó fájl: '.$this->zipFileName);
                }

                $zip = new \ZipArchive();
                if ($zip->open($this->zipFileName) === TRUE) {
                    $body = $zip->getFromName('body.txt');
                    $zip->close();
                }

                if ($model->is_html) {

                    // $msg = toHtml($model->body);
                    $msg = toHtml($body);

                    $embeddedAttachments = $this->getImagesFromMsg($msg);

                    if (!empty($embeddedAttachments)) {
                        foreach ($embeddedAttachments as $cid => $value) {
                            switch ($value['type']) {
                                case 'image/gif':
                                    $name = $cid . '.gif';
                                    break;
                                case 'image/jpeg':
                                    $name = $cid . '.jpg';
                                    break;
                                case 'image/png':
                                    $name = $cid . '.png';
                                    break;
                                case 'image/vnd.microsoft.icon':
                                    $name = $cid . '.ico';
                                    break;
                                default:
                                    $name = $cid;
                                    break;
                            }
                            $mail->addStringEmbeddedImage($value['content'], $cid, $name, 'base64', $value['type']);
                        }
                    }

                    $mail->Body = toHtml($msg);
                } else {
                    $mail->AltBody = $body;
                }

                if ($mail->send()) {
                    $model->state = 3;
                    $model->sent_date = date('Y-m-d H:i:s');
                    $model->user_id = config('user_id');
                    $model->save();
                    
                    echo "send success";
                    $daily = DailyEmails::query()
                        ->where('day', date('Y-m-d'))
                        ->where('priority', $model->priority)
                        ->where('entity_id', $model->entity_id)
                        ->first();
                    $daily->successful = $daily->successful + 1;
                    $daily->save();

                    hwslog(event:'E-mail küldés', message:"Sikeres továbbítás\n\nCímzett: \n" . $model->addressee . "\n\nE-mail tárgya:\n" . $model->subject, modul_azon:'notificationcenter', entity_id:$model->entity_id);


                    rename(
                        $this->zipFileName, 
                        storage_path() . "/" . $generalParams['notification_email_path'] . $entityId . '/sended/' . $model->id . '.zip'
                    );
                    
                    usleep(46700);
                }
            } 
            // catch (MaxAttemptsExceededException $e) {

            // }
            catch (GlobalException $e) {
                hwslog(event:'E-mail küldés', message: "Sikertelen továbbítás\n\npróbálkozás indítása\n\nCímzett: \n" . $model->addressee . "\n\nE-mail tárgya:\n" . $model->subject,  err: $e->getMessage(), modul_azon:'notificationcenter', entity_id:$model->entity_id);

                if (strpos($e->getMessage(), "Could not connect to SMTP host") !== false and 
                    $this->attempts() < $this->tries
                ) {
                    hwslog(event:'E-mail küldés', message: "Sikertelen továbbítás\n\npróbálkozás indítása\n\nCímzett: \n" . $model->addressee . "\n\nE-mail tárgya:\n" . $model->subject,  err: $e->getMessage(), modul_azon:'notificationcenter', entity_id:$model->entity_id);
                    sleep(30);
                    $this->release(180);
                    throw new \Exception();
                }

                    
                $this->job->fail('max attempts exceeded');
                $model->sent_error = $e->getMessage();
                $model->state = 2;
                $model->save();
                
                rename(
                    $this->zipFileName, 
                    storage_path() . "/" . $generalParams['notification_email_path'] . $entityId . '/error/' . $model->id . '.zip'
                );
                
                hwslog(event:'E-mail küldés', message: "Sikertelen továbbítás\n\nCímzett: \n" . $model->addressee . "\n\nE-mail tárgya:\n" . $model->subject,  err: $e->getMessage(), modul_azon:'notificationcenter', entity_id:$model->entity_id);
                usleep(300000);
                throw new \Exception();
            }
        }
    }
}
