<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Users;

class PauseRMC extends Controller3
{
    public $model = Users::class;

    public $log_event_action = ['update' => 'Felhasználó szüneteltetése'];

    public $permissons = [
        'update' => ['SysAdmin', 'users_admin', 'user_szunet'],
    ];

    public function update(Request $request, $id)
    {
        if ($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');
        Users::$allStates = true;
        $model = Users::findOne($id);
        if (!$model) sendError('A rekord nem található!');

      
        sendMessage('user_state_szunet', $model, [
            'name' => $model->name,
            'login' => $model->login,
            'email' => $model->email,
            'user_id' => $model->id,
        ]);
        $model->state_id = 5;

        $model->inactive_date = null;
        $model->last_login = date('Y-m-d H:i:s');
        $model->save();
        return [];
    }
}
