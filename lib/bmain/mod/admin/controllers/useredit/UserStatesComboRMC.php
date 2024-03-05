<?php

namespace mod\admin\controllers\useredit;

use App\Models\User;
use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\UserStates;

class UserStatesComboRMC extends Controller3
{

    public $model = UserStates::class;

    public $log_event_id = 'Állapotok';

    public $select = [
        'name' => 'state',
        'value' => 'id'
    ];

    public function list(Request $request){
        // return $this->defaultList();
        $query = $this->model::query()->select($this->select)->search();
        return $query->orderBy('priority', 'asc');
    }

}
