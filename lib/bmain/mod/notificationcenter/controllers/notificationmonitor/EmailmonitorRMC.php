<?php

namespace mod\notificationcenter\controllers\notificationmonitor;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\notificationcenter\models\DailyEmails;
use mod\notificationcenter\models\Emails;
use mod\notificationcenter\models\Jobs;
use mod\notificationcenter\models\Pushs;
use mod\notificationcenter\models\QueuesCount;

// use Illuminate\Support\Facades\Artisan;
// use Symfony\Component\Console\Output\BufferedOutput;





class EmailmonitorRMC extends Controller3
{

    public $model = Jobs::class;

    public $log_event_id = 'Kiküldés monitorozás / E-mail továbbító processzek';
    public $log_event_action = ['update' => 'kezelés'];


    public $permissons = [
        'list' => true,
        'view' =>true,
        'update' => 'cnadmin',
    ];

    public $metaNoUpdate = true;

    // public function cols()
    // {
    //     return [
    //         'run' => [
    //             'sortable' => false,
    //             'title' => "Állapot",
    //             'cu' => false,
    //             'search' => false,
    //             'list' => true
    //         ],
    //         'queued_db' => [
    //             'sortable' => false,
    //             'title' => "Továbbításra vár",
    //             'cu' => false,
    //             'search' => false,
    //             'list' => true
    //         ],
    //         'state' => [
    //             'sortable' => false,
    //             'title' => "Továbbításra vár",
    //             'cu' => false,
    //             'search' => false,
    //             'list' => false
    //         ],

    //     ];
    // }

    public function cols()
    {
        return [
            'type' => [
                'sortable' => false,
                'title' => "Típus",
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'priority' => [
                'title' => 'Prioritás',
                'sortable' => false,
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'queued_db' => [
                'sortable' => false,
                'title' => "Továbbításra vár",
                'cu' => false,
                'search' => false,
                'list' => true
            ],

        ];
    }


    public function list(Request $request)
    {
        if ($request->query('getMeta')){
            $ret = null;
            $this->getMeta($ret);
            $ret['meta'] = [
                'id' => [
                    "title" => "azonosító",
                    "type" => "integer",
                    "create" => false,
                    "update" => false,
                    "validation" => null,
                    "details" => false,
                    "smalldetails" => false,
                    "search" => false,
                ]
            ];
            $ret['reload'] = true;
            return $ret;
        }

        $emailCountLow = Emails::query()->where('priority', 1)->where('state',1)->entity()->count();
        $emailCountHigh = Emails::query()->where('priority', 2)->where('state',1)->entity()->count();
        $pushCountLow = Pushs::query()->where('priority', 1)->where('state',1)->entity()->count();
        $pushCountHigh = Pushs::query()->where('priority', 2)->where('state',1)->entity()->count();

        return [
            'data' => [
                [
                    'id' => 1,
                    'type' => 'Email',
                    'queued_db' => $emailCountLow,
                    'priority' => 'Alacsony'
                ],
                [
                    'id' => 1,
                    'type' => 'Email',
                    'queued_db' => $emailCountHigh,
                    'priority' => 'Magas'
                ],
                [
                    'id' => 2,
                    'type' => 'Push',
                    'queued_db' => $pushCountLow,
                    'priority' => 'Alacsony'
                ],
                [
                    'id' => 2,
                    'type' => 'Push',
                    'queued_db' => $pushCountHigh,
                    'priority' => 'Magas'
                ]
            ],

            'total' => 1
        ];
        
        
        // $status = [];
        // foreach (["email_low", "email_high"] as $svc) {
        //     $execstring = base_path()."/bin/srvctl is-active $svc";
        //     $output = [];
        //     exec($execstring, $output, $exitcode);
        //     if ($exitcode > 0) {
        //         $status[$svc] = "hiba1";
        //     } elseif (empty($output)) {
        //         $status[$svc] = "hiba2";
        //     } else {
        //         if ($output[0] == "active") {
        //             $status[$svc] = "fut";
        //         } else {
        //             $status[$svc] = "nem fut";
        //         }
        //     }
        // }

        // $dayPrio1 = QueuesCount::query()
        //             ->where('type', 'email')
        //             ->where('priority', 1)
        //             ->where('entity_id', getEntity())
        //             ->first();

        // $dayPrio2 = QueuesCount::query()
        //             ->where('type', 'email')
        //             ->where('priority', 2)
        //             ->where('entity_id', getEntity())
        //             ->first();

        // $out = [
        //     [
        //         'id' => 1,
        //         'run' => 'Alacsony prioritású processz '. $status['email_low'],
        //         "queued_db" => $dayPrio1?->count ?: 0,
        //         "state" => $status['email_low']
        //     ],
        //     [
        //         'id' => 2,
        //         'run' => 'Magas prioritású processz '. $status['email_high'],
        //         "queued_db" => $dayPrio2?->count ?: 0,
        //         "state" => $status['email_high']
        //         ]
        // ];



        // return ['data' => $out, 'total' => 2];
    }

    // public function start(Request $request, $id)
    // {
    //     switch ($id) {
    //         case 1:
    //             exec(base_path().'/bin/srvctl restart email_low');
    //             break;
                
    //         case 2:
    //             exec(base_path().'/bin/srvctl restart email_high');
    //             break;
                
    //         default:
    //             # code...
    //             break;
    //     }
    // }

    // public function stop(Request $request, $id)
    // {
    //     switch ($id) {
    //         case 1:
    //             exec(base_path().'/bin/srvctl stop email_low');
    //             break;
                
    //         case 2:
    //             exec(base_path().'/bin/srvctl stop email_high');
    //             break;
                
    //         default:
    //             # code...
    //             break;
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     if ($request->op == 'start'){
    //         $this->start($request, $id);
    //     }
    //     if ($request->op == 'stop'){
    //         $this->stop($request, $id);
    //     }
    //     return [];
    // }
}
