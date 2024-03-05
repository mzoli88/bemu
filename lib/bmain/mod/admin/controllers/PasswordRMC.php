<?php

namespace mod\admin\controllers;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use mod\admin\models\Users;

class PasswordRMC extends Controller3
{
    public $log_event_id = 'Jelszó';
    public $log_event_action = ['create' => 'módosítás'];

    public function create(Request $request)
    {
        
        $user = Users::findOne(getUserId());

        $pw_change = false;
        $password_valid_days = getParam('password_valid_days', 'admin') ?: 0;
        if ($password_valid_days != 0) {
            $chdate = strtotime(getUser()->last_password_modify . ' + ' . $password_valid_days . ' days');
            $pw_change = time() > $chdate;
            if(!$pw_change)return [];
        }else{
            return [];
        }

        if ($request->password != $request->password_confirmation) {
            sleep(1);
            return response()->json([
                'password_confirmation' => 'Jelszó mezők tartalma nem egyezik meg!'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        
        $user->save();

        return [];    }
}
