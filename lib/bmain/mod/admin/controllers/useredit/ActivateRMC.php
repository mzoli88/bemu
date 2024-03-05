<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller;
use Illuminate\Http\Request;
use mod\admin\models\Adminkonfig;

class ActivateRMC extends Controller
{

    public function view(Request $request,$id){
        Adminkonfig::setParams([
            'active_user' => $id
        ]);
        session_destroy();
        emu_user_init();
        return ["success" => true];
    }    

}
