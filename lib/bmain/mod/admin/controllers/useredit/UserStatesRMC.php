<?php

namespace mod\admin\controllers\useredit;

use App\Models\User;
use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\UserStates;

class UserStatesRMC extends Controller3
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

        if ($request->has('user_id')) {
            $user = User::find($request->user_id);
            switch ($user->state_id) {
                case 1:
                    $query->whereIn('id', [1,4]);
                    break;
                
                case 2:
                    $query->whereIn('id', [2,3,5]);
                    break;
                
                case 5:
                    $query->whereIn('id', [2,3,5]);
                    break;

                case 6:
                    $query->whereIn('id', [2,3,6]);
                    break;

                case 7:
                    $query->whereIn('id', [2,3,7]);
                    break;
            }
        }
        return $query->orderBy('priority', 'asc');
    }

}
