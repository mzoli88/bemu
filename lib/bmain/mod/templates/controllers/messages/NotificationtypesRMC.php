<?php

namespace mod\templates\controllers\messages;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Entities;
use mod\templates\models\Messages;

class NotificationtypesRMC extends Controller3
{

    public $model = Entities::class;

    public $log_event_id = 'Események';


    public function list(Request $request)
    {
        $data = [];
        $messageTypes = config('messageTypes');

        // $have_messages = Messages::entity()->where('modul_azon',$request->parent_id)->get()->pluck('type')->toArray();

        if ($request->parent_id && array_key_exists($request->parent_id, $messageTypes)) {
            $data = collect($messageTypes[$request->parent_id])->map(function ($v, $k) {
                return [
                    'value' => $k,
                    'name' => $v['name'],
                    'chanels' => $v['chanels'],
                ];
            })->sortBy('name')->values()->toArray();
        }

        return [
            'data' => $data
        ];
    }
}
