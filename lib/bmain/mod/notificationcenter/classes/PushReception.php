<?php

namespace mod\notificationcenter\classes;

use mod\notificationcenter\models\Priorities;

use Illuminate\Support\Facades\Bus;

use Exception as GlobalException;
use mod\notificationcenter\Jobs\SendPush;
use mod\notificationcenter\models\DailyPushs;
use mod\notificationcenter\models\Pushs;
use Illuminate\Support\Facades\DB;
use mod\notificationcenter\models\QueuesCount;

class PushReception {
    
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

    public static function HasPushEntityParams($entityId)
    {
        self::$use_keys['push']  = [
            "push_fb_url_",
            "push_fb_key_",
            "push_fb_android_channel_id_",
        
            "proxy_use_",
            "proxy_host_",
            "proxy_port_",
            "proxy_user_",
            "proxy_pw_",
        ];

        $entityParams = self::EntityParams(self::$use_keys['push'], $entityId);

        if (empty($entityParams)){
            sendError("Hibányzó entitás paraméterek!");
            return false;
        }

        return $entityParams;
    }

    public static function Push(
        $deviceToken, 
        $title, 
        $body, 
        $entityId = null, 
        $modul = null, 
        $modulRecordId = null, 
        $priority = 1,
        $callback = null
        )
    {

        if (empty( $entityId)){
            $entityId = getEntity();
        }

        $entityParams = self::HasPushEntityParams($entityId);

        if (!Priorities::query()->where('id', $priority)->exists()){
            sendError("Hibás prioritás!");
        }

        if (empty($deviceToken)){
            sendError("Hibás device token!");
        }

        $push = new Pushs();

        $priorities = Priorities::getCache();

        $push->entity_id = $entityId;
        $push->title = $title;
        $push->body = $body;
        // $push->badge = $badge;
        // $push->icon = $icon;
        // $push->click_action = $click_action;
        // $push->sound = $sound;
        // $push->tag = $tag;
        $push->priority = $priority;
        $push->modul_record_id = $modulRecordId;
        $push->state = 1;
        $push->modul_id = $modul;
        $push->token = $deviceToken;
        $push->callback = $callback;
        $push->sender_params = json_encode($entityParams);

        $push->save();

        $dailyMail = DailyPushs::updateOrInsert(
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
                'type' => "push",
            ],
            [
                'priority' => $priority,
                'entity_id' => $entityId,
                'type' => "push",
                'count' => DB::raw('count + 1')
            ]
        );

        $Job = (new SendPush($push->id))->onQueue("push_" .  strtolower($priorities[$priority]) . "_" . $entityId);
        Bus::dispatch($Job);

        return $push->id; 


    }

}