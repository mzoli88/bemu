<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Nevek;
use Illuminate\Support\Facades\DB;


class UsersRMC extends Controller3
{


    public $model = Nevek::class;


    public $use_tarnsaction = [];


    public function list(Request $request)
    {
       
    
        return $this->defaultList([
            '*',
           "user_has" => DB::raw('SELECT count(*) FROM b_nagycsoport_nevek WHERE b_nagycsoport_nevek.nevek_id = nevek.id AND b_nagycsoport_nevek.b_nagycsoport_id = '.$request->group_id),
        ]);
    }
   
}
