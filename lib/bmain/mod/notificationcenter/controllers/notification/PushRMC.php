<?php

namespace mod\notificationcenter\controllers\notification;

use Carbon\Carbon;
use hws\rmc\Controller3;
use Illuminate\Http\Request;

use mod\notificationcenter\models\Priorities;

use Illuminate\Support\Facades\Bus;
use mod\notificationcenter\Jobs\SendPush;
use mod\notificationcenter\models\DailyPushs;
use mod\notificationcenter\models\Pushs;

use mod\notificationcenter\models\QueuesCount;

use Illuminate\Support\Facades\DB;
class PushRMC extends Controller3
{

    public $model = Pushs::class;

    protected static $use_keys = [];

    public $log_event_id = 'Push';
    public $log_event_action = ['update' => 'újraküldés'];

    public $permissons = [
        'list' => true,
        'view' =>true,
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];

    public $metaNoUpdate = true;
    public $metaNoCreate = true;

    public function cols()
    {

        return [
            'id' => [
                'title' => 'Azonosító',
                'cu' => false,
                'search' => false,
                'list' => false
            ],

            'priority_name' => [
                'sortable' => false,
                'title' => "Proiritás",
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'priority' => [
                'sortable' => false,
                'cu' => false,
                'search' => [
                    "type" => "combo",
                    "store" => "priority"
                ],
                'list' => false
            ],
            'state' => [
                'sortable' => false,
                'cu' => false,
                'search' => [
                    "type" => "combo",
                    "store" => "state"
                ],
                'list' => false
            ],
            'state_name' => [
                'title' => 'Állapot',
                'sortable' => false,
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'title' => [
                'sortable' => false,
                'title' => 'Üzenet tárgya',
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'modul_id' => [
                'sortable' => false,
                'title' => 'Modul',
                'cu' => false,
                'search' => [
                    'type' => 'combo',
                    'store' => 'moduls'
                ],
                'list' => true
            ],
            'created_at' => [
                'title' => 'Létrehozás dátuma',
                'sortable' => false,
                'cu' => false,
                'search' => 'dateinterval',
                'list' => true
            ],
            'sent_date' => [
                'title' => 'Továbbitás dátuma',
                'sortable' => false,
                'cu' => false,
                'search' => 'searchdate',
                'list' => true
            ],

            'token' => [
                'sortable' => false,
                'cu' => false,
                'search' => true,
                'list' => true
            ],
        ];
    }

    public $select = [
        'id',
        'priority',
        'state',
        'priority_name' => 'Rel_priority.label',
        'state_name' => 'Rel_state.notify_state',
        'title',
        'modul_id',
        'created_at',
        'sent_date',
        'token'
    ];


    // public function create(Request $request)
    // {
    //     $data = $request->all();
    //     $request->query->add([
    //         'per-page' => 10000000, 
    //         'page' => 1
    //     ]);
    //     $_GET = $data;
    //     $allDataModel = $this->defaultList(select: $this->select, Collection: 'list')->entity();
    //     $allData = $allDataModel->get();
    //     if ($allData){
    //         foreach ($allData->toArray() as $key => $row) {

    //             $return = $this->ReSend($row['id']);
    //             if ($return){
    //                 hwslog(['audit', 'security'], 'Push küldés', 'Sikeres queue mentés');
    //             }else{
    //                 hwslog(['audit', 'security'], 'Push küldés', 'Sikeretelen queue mentés');
    //             }
    //         }
    //     }
    //     return [];
    // }

    public function list(Request $request)
    {
        if ($request->has('getMeta')){
            $currentDateTime = Carbon::now();

           $days = getParam('notification_list_date') ?: 30;
           $newDateTime = $currentDateTime->subDays($days); 
           $this->defaultSearch = [ 
               'created_at>' =>  $newDateTime->format('Y-m-d'), 
               'created_at<' =>  Carbon::now()->format('Y-m-d') 
           ];
       }

        return $this->defaultList(select: $this->select, Collection: 'list', searchOverride: [
            'token' => function ($q1, $values) {   
                $q1->where('token', $values);
            }
        ])->entity();
       
    }

    public function update(Request $request, $id)
    {

        $return = $this->ReSend($id);
        
        if ($return){
            hwslog(log_chanels:['audit', 'security'], event:'Push küldés', message:'Sikeres várakozási sorba rögzítés',  modul_azon:'notificationcenter');
        }else{
            hwslog(log_chanels:['audit', 'security'], event:'Push küldés', message:'Sikeretelen várakozási sorba rögzítés', err:'Sikeretelen várakozási sorba rögzítés',  modul_azon:'notificationcenter');
        }
        
        return [];
    }

    protected function ReSend($id)
    {
        $push = Pushs::query()->find($id);

        $entityParams = self::HasPushEntityParams($push->entity_id);
        $push->sender_params = json_encode($entityParams);
        $push->state = 1;

        $priorities = Priorities::getCache();

        $Job = (new SendPush($push->id))->onQueue("push_" .  strtolower($priorities[$push->priority]) . '_' . $push->entity_id);
        Bus::dispatch($Job);

        $push->save();


        // $daily = DailyPushs::query()
        //         ->where('day', date('Y-m-d'))
        //         ->entity()
        //         ->first();
        // if (!$daily){
        //     $daily = new DailyPushs;

        //     $daily->day = date('Y-m-d');
        //     $daily->entity_id = getEntity();
        //     $daily->resend = 0;
        //     $daily->priority = $push->priority;
        // }

        // QueuesCount::updateOrInsert(
        //     [
        //         'priority' => $push->priority,
        //         'entity_id' => getEntity(),
        //         'type' => "push",
        //     ],
        //     [
        //         'priority' => $push->priority,
        //         'entity_id' => getEntity(),
        //         'type' => "push",
        //         'count' => DB::raw('count + 1')
        //     ]
        // );

        // $daily->resend = $daily->resend + 1;
        // $daily->save();

        return true;
    }


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
}
