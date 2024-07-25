<?php

namespace mod\notificationcenter\controllers\notification;

use mod\notificationcenter\models\Priorities;
use hws\rmc\Controller3;
use Illuminate\Http\Request;


class PriorityRMC extends Controller3
{

    public $model = Priorities::class;

    public $log_event_id = 'Prioritás';

    public $permissons = [
        'list' => true,
        'view' =>true,
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];




    public $select = [
        'name' => 'label',
        'value' =>  'id',
    ];


    public function list(Request $request)
    {
        return $this->defaultList($this->select);
    }
}
