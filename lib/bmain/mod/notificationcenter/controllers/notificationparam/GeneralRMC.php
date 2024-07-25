<?php

namespace mod\notificationcenter\controllers\notificationparam;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Params;

class GeneralRMC extends Controller3
{

    public $model = Params::class;

    public $log_event_id = 'Általános paraméterek';
    public $log_event_action = ['create' => 'módosítás'];

    public $permissons = [
        'list' => true,
        'view' =>true,
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];

    public $select = [
        '*',
    ];


    public function list()
    {
        $params = getParams();
        $out = ['notification_list_date' => array_key_exists('notification_list_date', $params) ? $params['notification_list_date'] : null];
        $out = ['notification_email_path' => array_key_exists('notification_email_path', $params) ? $params['notification_email_path'] : null];
        return $out; 
    }

    public function create()
    {
        $params = request()->all();

        setParams($params);
        
        return [];
    }

    public function update(Request $request, $id)
    {
        return $this->create();
    }
}
