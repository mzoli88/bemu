<?php

namespace mod\admin\controllers\passwordchange;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use mod\admin\models\Users;

class UserRMC extends Controller3
{

    public $log_event_action = [
        'create' => 'Jelszó módosítás'
    ];


    public $model = Users::class;


    public function create(Request $request)
    {
        if ($request->password != $request->password_confirmation) {
            sleep(1);
            return response()->json([
                'password_confirmation' => 'Jelszó mezők tartalma nem egyezik meg!'
            ], 422);
        }

        $user = Users::findOne(getUserId());

        if (!Hash::check($request->current_password, $user->password)) {
            sleep(1);
            return response()->json([
                'current_password' => 'Hibás jelszó!'
            ], 422);
        }

        $user->password = Hash::make($request->password);

        $user->save();

        return [];
    }
}
