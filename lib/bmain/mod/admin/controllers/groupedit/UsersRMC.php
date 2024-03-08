<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Nevek;

class UsersRMC extends Controller3
{


    public $model = Nevek::class;


    public $use_tarnsaction = [];


    public function list(Request $request)
    {
        return $this->defaultList();
    }
   
}
