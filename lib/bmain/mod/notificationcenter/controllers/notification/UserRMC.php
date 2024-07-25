<?php

namespace mod\notificationcenter\controllers\notification;

use mod\admin\models\Users;
use hws\rmc\Controller3;
use Illuminate\Http\Request;


class UserRMC extends Controller3
{

    public $model = Users::class;

    public $log_event_id = 'Felhasználó';


    public $permissons = [
        'list' => 'cnadmin',
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];




    public $select = [
        'name' => 'name',
        'value' =>  'id',
    ];


    public function list(Request $request)
    {
        return $this->defaultList($this->select)->entity();
    }
}
