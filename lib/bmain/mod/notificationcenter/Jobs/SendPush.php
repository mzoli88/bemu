<?php
 
namespace mod\notificationcenter\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// use Exception as GlobalException;

// use hws\rmc\Model;
// use mod\notificationcenter\models\DailyPushs;

use mod\notificationcenter\models\Pushs;

// use mod\notificationcenter\models\QueuesCount;
// use Illuminate\Support\Facades\DB;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Exception\Messaging\AuthenticationError;

use Kreait\Firebase\Http\HttpClientOptions;
// use mod\notificationcenter\models\ConsumerTokens;

class SendPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 
    protected $puhs = null;

    public $tries = 10;

    public $timeout = 20;

    public $waiting = 60;
    
    /**
     * Create a new job instance.
     */
    public function __construct($puhs) 
    {
        // Logger()->info('__construct: ' . $puhs);
        $this->puhs = $puhs;
    }
 
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $model = Pushs::query()->find($this->puhs);

        $serviceAccount = base_path() . '/mod/notificationcenter/push/push_' . $model->entity_id . '.json';

        $params = json_decode($model->sender_params, true);

        $factory = (new Factory)
            ->withServiceAccount($serviceAccount);

        $options = HttpClientOptions::default();
            
        $options = $options->withTimeOut(10);
            
        if ($params['proxy_use_'] == "I"){
            //"http://username:password@192.168.16.1:10".
            $options = $options->withProxy('tcp://' . $params['proxy_host_'] . ':' . $params['proxy_port_']); 
        }

        $factory = $factory->withHttpClientOptions($options);

        // dump($factory);

        $messaging = $factory->createMessaging();

        $title = $model->title;
        $body = $model->body;
        $token = $model->token;

        $fbNotification = FirebaseNotification::create($title, $body);

        $data = [
            'title' => $title,
            'body' => $body,
            'android_channel_id' => $params['push_fb_android_channel_id_'],
            'sound' => $model->sound,
            'actionType' => '',
            'actionData' => '',
        ];

        $message = CloudMessage::withTarget(MessageTarget::TOKEN, $token)
            ->withNotification($fbNotification)
            ->withData($data);

            $success = false;
            $sended = 'N';
            $resultText = '';
            try {
                // Üzenet küldése
                $pushResult = $messaging->sendMulticast($message, $token);
                $success = $pushResult->successes()->count() > 0;    
                // dump($pushResult);
                if ($success) {
                    $sended = 'I';
                    $resultText = 'Sikeres push üzenet küldés';

                    $model->state = 3;
                    $model->fmc_result_json = json_encode($resultText);
                    $model->sent_date = date('Y-m-d H:i:s');
                    $model->sender_params = '';
                    $model->save();

                    hwslog(event:'Push küldés', message:"Sikeres továbbítás\n\nCímzett token: ".$model->token, modul_azon:'notificationcenter', entity_id:$model->entity_id);
                } else {
                    $connectionError = "Kreait\Firebase\Exception\Messaging\ApiConnectionFailed";
                    $notFoundError = "Kreait\Firebase\Exception\Messaging\NotFound";
                    $authError = "Kreait\Firebase\Exception\Messaging\AuthenticationError";
                    
                    if (($pushResult->getItems()[0]->error() instanceof $notFoundError) 
                        and isset($pushResult->getItems()[0]->error()->getTrace()[0]['args'][0]['error']['code'])
                        and in_array($pushResult->getItems()[0]->error()->getTrace()[0]['args'][0]['error']['code'], [404]))
                    {
                            foreach ($pushResult->unknownTokens() as $unknownToken) {
                                $resultText = $resultText . ' Ismeretlen token: ' . $unknownToken;
                                $model->state = 2;
                                $model->sent_error = $resultText;
                                $model->sent_date = date('Y-m-d H:i:s');
                                $model->can_be_deleted = 'I';
                                $model->save();
                            }
                            foreach ($pushResult->invalidTokens() as $invalidToken) {
                                $resultText = $resultText . ' Érvénytelen token: ' . $invalidToken;
                                $model->state = 2;
                                $model->sent_error = $resultText;
                                $model->sent_date = date('Y-m-d H:i:s');
                                $model->can_be_deleted = 'I';
                                $model->save();
                            }            
                        }
                    elseif (($pushResult->getItems()[0]->error() instanceof $connectionError) 
                        and isset($pushResult->getItems()[0]->error()->getTrace()[0]['args'][0]->getHandlerContext()['errno']) 
                        and $pushResult->getItems()[0]->error()->getTrace()[0]['args'][0]->getHandlerContext()['errno'] == 28)
                    {
                        if ($this->attempts() < $this->tries){
                            $this->release($this->waiting);
                        }else {
                            $model->state = 2;
                            $model->sent_error = 'TIMEOUT';
                            $model->sent_date = date('Y-m-d H:i:s');
                            $model->save();

                            hwslog(event:'Push küldés', message:"Sikertelen továbbítás \n\nCímzett token: \n".$model->token."\n\nHiba: ". "TIMEOUT", err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
                        }
                    }
                    elseif (($pushResult->getItems()[0]->error() instanceof $authError) 
                        and isset($pushResult->getItems()[0]->error()->getTrace()[0]['args'][0]['error']['status'])
                        and $pushResult->getItems()[0]->error()->getTrace()[0]['args'][0]['error']['status'] == "PERMISSION_DENIED")
                    {
                        if ($this->attempts() < $this->tries){
                            $this->release($this->waiting);
                        }else {
                            $model->state = 2;
                            $model->sent_error = 'PERMISSION_DENIED';
                            $model->sent_date = date('Y-m-d H:i:s');
                            $model->save();

                            hwslog(event:'Push küldés', message:"Sikertelen továbbítás \n\nCímzett token: \n".$model->token."\n\nHiba: ". "PERMISSION_DENIED", err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
                        }
                    }else{
                        if ($this->attempts() < $this->tries){
                            $this->release($this->waiting);
                        }else {
                            $model->state = 2;
                            $model->sent_error = $resultText;
                            $model->sent_date = date('Y-m-d H:i:s');
                            $model->save();

                            hwslog(event:'Push küldés', message:"Sikertelen továbbítás \n\nCímzett token: \n".$model->token."\n\nHiba: ".$resultText, err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
                        }
                    }
                    $resultText = 'Sikertelen push üzenet küldés';
                }
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                // dump($e);
                $resultText = $resultText . 'Hiba történt az üzenet küldése során: ' . $e->getMessage();

                $model->state = 2;
                $model->sent_error = $resultText;
                $model->sent_date = date('Y-m-d H:i:s');
                $model->save();

                hwslog(event:'Push küldés', message:"Sikertelen továbbítás \n\nCímzett token: \n".$model->token."\n\nHiba: ".$e->getMessage().", fájl: ".$e->getFile().", sor: ".$e->getLine(), err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
            }  catch (\Kreait\Firebase\Exception\FirebaseException $e) {
                // dump($e);
                $resultText = $resultText . 'Firebase hiba történt: ' . $e->getMessage();
                $model->state = 2;
                $model->sent_error = $resultText;
                $model->sent_date = date('Y-m-d H:i:s');
                $model->save();
                
                hwslog(event:'Push küldés', message:"Sikertelen továbbítás \n\nCímzett token: \n".$model->token."\n\nHiba: ".$e->getMessage().", fájl: ".$e->getFile().", sor: ".$e->getLine(), err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
            }
        // try {
        //     // Üzenet küldése
        //     // $result = $messaging->send($message);
        //     $result = $messaging->sendMulticast($message, $token);




        //     dump($result);

        //     $model->state = 3;
        //     $model->fmc_result_json = json_encode($result);
        //     $model->sent_date = date('Y-m-d H:i:s');
        //     $model->save();

        //     hwslog(event:'Push küldés', message:"Sikeres továbbítás\n\nCímzett token: ".$model->token, modul_azon:'notificationcenter', entity_id:$model->entity_id);
        // } catch (\Kreait\Firebase\Exception\MessagingException $e) { //pl: not found token
        //     dump($e->getCode());

        //     switch ($e->getCode()) {
        //         case 400:
        //         case 401:
        //         case 403:
        //             $model->state = 2;
        //             break;
        //         case 404:
        //             $model->state = 4;
        //             break;
        //         default:
        //             if ($this->attempts() < $this->tries){
        //                 $model->state = 2;
    
        //                 $this->release(180);
        //             }
        //             break;
        //     }
        //     $model->sent_error = $e->getMessage();
        //     $model->sent_date = date('Y-m-d H:i:s');
        //     $model->save();

        //     hwslog(event:'Push küldés', message:"Sikertelen továbbítás 1\n\nCímzett token: \n".$model->token."\n\nHiba: ".$e->getMessage().", fájl: ".$e->getFile().", sor: ".$e->getLine(), err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
        // } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
        //     $model->state = 2;
        //     $model->sent_error = $e->getMessage();
        //     $model->sent_date = date('Y-m-d H:i:s');
        //     $model->save();
        
        //     hwslog(event:'Push küldés', message:"Sikertelen továbbítás 2\n\nCímzett token: \n".$model->token."\n\nHiba: ".$e->getMessage().", fájl: ".$e->getFile().", sor: ".$e->getLine(), err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
        // } catch (GlobalException $e) {
        //     $model->state = 2;
        //     $model->sent_error = $e->getMessage();
        //     $model->sent_date = date('Y-m-d H:i:s');
        //     $model->save();
        
        //     hwslog(event:'Push küldés', message:"Sikertelen továbbítás 3\n\nCímzett token: \n".$model->token."\n\nHiba: ".$e->getMessage().", fájl: ".$e->getFile().", sor: ".$e->getLine(), err:'Sikertelen push küldés', modul_azon:'notificationcenter', entity_id:$model->entity_id);
        // }


    }
}
