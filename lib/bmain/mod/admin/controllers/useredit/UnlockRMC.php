<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Users;

class UnlockRMC extends Controller3
{
    public $model = Users::class;

    public $permissons = [
        'update' => ['SysAdmin', 'users_admin', 'user_lift_suspension', 'user_lift_lock', 'user_szunet'],
    ];

    public function update(Request $request, $id)
    {
        if ($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');
        Users::$allStates = true;
        $model = Users::findOne($id);
        if (!$model) sendError('A rekord nem található!');

        switch ($model->state_id) {
            case 7:
                sendMessage('user_lift_suspension', $model, [
                    'name' => $model->name,
                    'login' => $model->login,
                    'email' => $model->email,
                    'user_id' => $model->id,
                ]);
                hwslog(null,'Felfüggesztett felhasználó aktiválása');
                break;
            case 6:
                sendMessage('user_lift_lock', $model, [
                    'name' => $model->name,
                    'login' => $model->login,
                    'email' => $model->email,
                    'user_id' => $model->id,
                ]);
                hwslog(null,'Zárolt felhasználó aktiválása');
                break;
            case 5:
                sendMessage('user_state_activated', $model, [
                    'name' => $model->name,
                    'login' => $model->login,
                    'email' => $model->email,
                    'user_id' => $model->id,
                ]);
                hwslog(null,'Szüneteltetett felhasználó aktiválása');
                break;
        }

        $model->state_id = 2;

        $model->inactive_date = null;
        $model->last_login = date('Y-m-d H:i:s');
        $model->save();
        return [];
    }
}
