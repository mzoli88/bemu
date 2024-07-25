<?php

namespace mod\notificationcenter\controllers\notification;

use Carbon\Carbon;
use hws\rmc\Controller3;
use Illuminate\Http\Request;

use mod\notificationcenter\models\Emails;
use mod\notificationcenter\Jobs\SendEmail;
use mod\notificationcenter\models\Priorities;

use Illuminate\Support\Facades\Bus;
use mod\notificationcenter\models\DailyEmails;
use mod\notificationcenter\models\QueuesCount;

use Illuminate\Support\Facades\DB;

class EmailRMC extends Controller3
{

    public $model = Emails::class;

    public $log_event_id = 'E-mail';
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

    protected static $use_keys = []; 

    public function cols()
    {

        return [
            'priority_name' => [
                'sortable' => false,
                'title' => 'Prioritás',
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

            'addressee' => [
                'sortable' => false,
                'cu' => false,
                'search' => true,
                'list' => true
            ],
            'subject' => [
                'sortable' => false,
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'modul_id' => [
                'title' => 'Modul',
                'sortable' => false,
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
        ];
    }

    public $select = [
        'id',
        'priority',
        'state',
        'priority_name' => 'Rel_priority.label',
        'state_name' => 'Rel_state.notify_state',
        'addressee',
        'subject',
        'modul_id',
        'created_at',
        'sent_date',
        'entity_id'
    ];

    public $defaultSearch = [];

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
            sendError("Hibányzó entitás paraméterek!");
            return false;
        }

        return $entityParams;
    }


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
            'addressee' => function ($q1, $values) {   
                $q1->where('addressee', $values);
            }
        ])->entity();
    }

    public function create(Request $request)
    {
        $data = $request->all();

        if ($data['ids']){
            foreach ($data['ids'] as $key => $rowId) {
                $return = $this->ReSend($rowId);
                if ($return){
                    hwslog(log_chanels:['audit', 'security'], event:'Email küldés', message:'Sikeres várakozási sorba rögzítés', modul_azon:'notificationcenter');
                }else{
                    hwslog(log_chanels:['audit', 'security'], event:'Email küldés', message:'Sikeretelen várakozási sorba rögzítés', err:'Sikeretelen várakozási sorba rögzítés', modul_azon:'notificationcenter');
                }
            }
        }
        return [];
    }

    public function update(Request $request, $id)
    {
        $return = $this->ReSend($id);
        
        if ($return){
            hwslog(log_chanels:['audit', 'security'], event:'Email küldés', message:'Sikeres várakozási sorba rögzítés', modul_azon:'notificationcenter');
        }else{
            hwslog(log_chanels:['audit', 'security'], event:'Email küldés', message:'Sikeretelen várakozási sorba rögzítés', err:'Sikeretelen várakozási sorba rögzítés', modul_azon:'notificationcenter');
        }
        return [];
    }

    protected function ReSend($id)
    {
        if (!$id){
            return false;
        }

        $email = Emails::query()->find($id);

        $entityParams = self::HasEmailEntityParams($email->entity_id);

        if ($email->sender_email_address){
            $entityParams['smtp_sender_name_'] = $email->sender_name;
            $entityParams['smtp_sender_email_'] = $email->sender_email_address;
        }

        $email->state = 1;
        $email->sender_params = json_encode($entityParams);
        $email->save();
        $priorities = Priorities::getCache();

        $generalParams = getParams('notificationcenter');
        $path = storage_path() . '/' . $generalParams['notification_email_path'];

        rename(
            $path . $email->entity_id . '/error/' . $email->id . '.zip', 
            $path . $email->entity_id . '/new/' . $email->id . '.zip'
        );

        $Job = (new SendEmail($email->id))->onQueue("email_" .  strtolower($priorities[$email->priority]) . '_' . $email->entity_id);
        Bus::dispatch($Job);

        // $daily = DailyEmails::query()
        //         ->where('day', date('Y-m-d'))
        //         ->entity()
        //         ->first();
        // if (!$daily){
        //     $daily = new DailyEmails;

        //     $daily->day = date('Y-m-d');
        //     $daily->entity_id = getEntity();
        //     $daily->resend = 0;
        //     $daily->priority = $email->priority;
        // }

        // $daily->resend = $daily->resend + 1;
        // $daily->save();

        // QueuesCount::updateOrInsert(
        //     [
        //         'priority' => $email->priority,
        //         'entity_id' => getEntity(),
        //         'type' => "email",
        //     ],
        //     [
        //         'priority' => $email->priority,
        //         'entity_id' => getEntity(),
        //         'type' => "email",
        //         'count' => DB::raw('count + 1')
        //     ]
        // );

        return true;
    }
}
