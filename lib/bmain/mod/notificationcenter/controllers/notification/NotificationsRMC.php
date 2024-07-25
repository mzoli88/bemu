<?php

namespace mod\notificationcenter\controllers\notification;

use Carbon\Carbon;
use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Params;
use mod\notificationcenter\models\Notifications;

class NotificationsRMC extends Controller3
{

    public $model = Notifications::class;

    public $log_event_id = 'Rendszerüzenet';
    public $log_event_action = ['update' => 'újraküldés'];

    public $permissons = [
        'list' => true,
        'view' =>true,
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];


    public function cols()
    {
        return [
            'id' => [
                'title' => 'Azonosító',
                'cu' => false,
                'search' => false,
                'list' => false
            ],
            'user_id' => [
                'title' => 'Név',
                'cu' => false,
                'search' => [
                    "type" => "combo",
                    "store" => "user"
                ],
                'list' => false
            ],
            'user_name' => [
                'title' => 'Név',
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'user_login' => [
                'title' => 'Felhasználónév',
                'cu' => false,
                'search' => true,
                'list' => true
            ],
            'date' => [
                'title' => 'Létrehozás dátuma',
                'cu' => false,
                'search' => 'dateinterval',
                'list' => true
            ],
            'modul_azon' => [
                'title' => 'Modul',
                'cu' => false,
                'search' => [
                    'type' => 'combo',
                    'store' => 'moduls'
                ],
                'list' => true,
            ],
            'type' => [
                'cu' => false,
                'search' => false,
                'list' => true
            ],
            'read' => [
                'cu' => false,
                'search' => 'cigennem',
                'list' => true
            ],
            'message' => [
                'cu' => false,
                'search' => false,
                'list' => false
            ]
        ];
    }

    public $select = [
        'id',
        'user_id',
        'user_name' => 'Rel_user.name',
        'user_login' => 'Rel_user.login',
        'date',
        'date2' => 'date',
        'user_id',
        'modul_azon',
        'type',
        'read',
        'message',
    ];


    public function list(Request $request)
    {
        if ($request->has('getMeta')){
            $currentDateTime = Carbon::now();

           $days = getParam('notification_list_date') ?: 30;
           $newDateTime = $currentDateTime->subDays($days); 
           $this->defaultSearch = [ 
               'date>' =>  $newDateTime->format('Y-m-d'), 
               'date<' =>  Carbon::now()->format('Y-m-d') 
           ];
       }

        return $this->defaultList(select: $this->select, Collection: 'list');
    }
}
