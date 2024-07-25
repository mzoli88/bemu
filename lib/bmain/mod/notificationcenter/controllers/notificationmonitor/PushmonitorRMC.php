<?php

namespace mod\notificationcenter\controllers\notificationmonitor;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\notificationcenter\models\DailyPushs;
use mod\notificationcenter\models\Jobs;
use mod\notificationcenter\models\Pushs;
use mod\notificationcenter\models\QueuesCount;

// use Illuminate\Support\Facades\Artisan;
// use Symfony\Component\Console\Output\BufferedOutput;


class PushmonitorRMC extends Controller3
{

    public $model = Jobs::class;

    public $log_event_id = 'Kiküldés monitorozás / Push üzenet továbbító processzek';
    public $log_event_action = ['update' => 'kezelés'];


    public $permissons = [
        'list' => true,
        'view' =>true,
        'update' => 'cnadmin',
    ];

    public $metaNoUpdate = true;

    public function cols()
    {
        return [
            'run' => [
                'sortable' => false,
                'title' => "Állapot",
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
            'state' => [
                'sortable' => false,
                'title' => "Továbbításra vár",
                'cu' => false,
                'search' => false,
                'list' => false
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

        $status = [];
        foreach (["push_low", "push_high"] as $svc) {
            $execstring = base_path()."/bin/srvctl is-active $svc";
            $output = [];
            exec($execstring, $output, $exitcode);
            if ($exitcode > 0) {
                $status[$svc] = "hiba1";
            } elseif (empty($output)) {
                $status[$svc] = "hiba2";
            } else {
                if ($output[0] == "active") {
                    $status[$svc] = "fut";
                } else {
                    $status[$svc] = "nem fut";
                }
            }
        }


        // $dayPrio1 = DailyPushs::query()
        //             ->where('day', date('Y-m-d'))
        //             ->where('priority', 1)
        //             ->where('entity_id', getEntity())
        //             ->first();
        // $dayPrio2 = DailyPushs::query()
        //             ->where('day', date('Y-m-d'))
        //             ->where('priority', 2)
        //             ->where('entity_id', getEntity())
        //             ->first();


        $dayPrio1 = QueuesCount::query()
                    ->where('type', 'push')
                    ->where('priority', 1)
                    ->where('entity_id', getEntity())
                    ->first();

        $dayPrio2 = QueuesCount::query()
                    ->where('type', 'push')
                    ->where('priority', 2)
                    ->where('entity_id', getEntity())
                    ->first();

        $out = [
            [
                'id' => 1,
                'run' => 'Alacsony prioritású processz '. $status['push_low'],
                "queued_db" => $dayPrio1?->count ?: 0,
                "state" => $status['push_low']
            ],
            [
                'id' => 2,
                'run' => 'Magas prioritású processz ' . $status['push_high'],
                "queued_db" => $dayPrio2?->count ?: 0,
                "state" => $status['push_high']
                ]
        ];

        // $out = [
        //     [
        //         "id" => 1,
        //         "priority" => "Alacsony",
        //         "queued_db" => Jobs::query()->where('queue' , 'push_low')->count(),
        //         "run" => (!empty($output) ? "Fut" : "Nem fut"),
        //         "day_all" => ($dayPrio1) ? $dayPrio1->to_out : 0,
        //         "day_new" => Pushs::query()->where('priority', 1)->whereDate("created_at", date("Y-m-d"))->where('state', 1)->entity()->count(),
        //         "day_ok" => ($dayPrio1) ? $dayPrio1->successful : 0,
        //         "day_error" => ($dayPrio1) ? $dayPrio1->failed : 0,
        //         "resend" => ($dayPrio1) ? $dayPrio1->resend : 0,            ],
        //     [
        //         "id" => 2,
        //         "priority" => "Magas",
        //         "queued_db" => Jobs::query()->where('queue' , 'push_high')->count(),
        //         "run" => (!empty($output2) ? "Fut" : "Nem fut"),
        //         "day_all" => ($dayPrio2) ? $dayPrio2->to_out : 0,
        //         "day_new" => Pushs::query()->where('priority', 2)->whereDate("created_at", date("Y-m-d"))->where('state', 1)->entity()->count(),
        //         "day_ok" => ($dayPrio2) ? $dayPrio2->successful : 0,
        //         "day_error" => ($dayPrio2) ? $dayPrio2->failed : 0,
        //         "resend" => ($dayPrio2) ? $dayPrio2->resend : 0,            ]
        // ];

        return ['data' => $out, 'total' => 2];
    }

    public function start(Request $request, $id)
    {
        switch ($id) {
            case 1:
                exec(base_path().'/bin/srvctl restart push_low');
                break;
                
            case 2:
                exec(base_path().'/bin/srvctl restart push_high');
                break;
                
            default:
                # code...
                break;
        }
    }

    public function stop(Request $request, $id)
    {
        switch ($id) {
            case 1:
                exec(base_path().'/bin/srvctl stop push_low');
                break;
                
            case 2:
                exec(base_path().'/bin/srvctl stop push_high');
                break;
                
            default:
                # code...
                break;
        }
    }

    public function update(Request $request, $id)
    {
        if ($request->op == 'start'){
            $this->start($request, $id);
        }
        if ($request->op == 'stop'){
            $this->stop($request, $id);
        }
        return [];
    }
}
