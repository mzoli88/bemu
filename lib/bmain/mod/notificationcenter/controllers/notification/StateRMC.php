<?php

namespace mod\notificationcenter\controllers\notification;

use mod\notificationcenter\models\States;
use hws\rmc\Controller3;
use Illuminate\Http\Request;


class StateRMC extends Controller3
{

    public $model = States::class;

    public $log_event_id = 'Állapot';


    public $permissons = [
        'list' => 'cnadmin',
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];




    public $select = [
        'name' => 'notify_state',
        'value' =>  'id',
    ];


    public function list(Request $request)
    {
        return $this->defaultList($this->select);
    }
}
