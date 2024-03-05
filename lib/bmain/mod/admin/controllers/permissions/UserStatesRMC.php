<?php

namespace mod\admin\controllers\permissions;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\UserStates;

class UserStatesRMC extends Controller3
{

    public $model = UserStates::class;

    public $log_event_id = 'Jogosultságok / Felhasználó állapotok';


    public $select = [
        'name' => 'state',
        'value' => 'id'
    ];

    public function list(Request $request)
    {
        return $this->model::query()->select($this->select)->whereIn('id',[1,2,5,6,7])->search()->orderBy('priority', 'asc');
    }
}
