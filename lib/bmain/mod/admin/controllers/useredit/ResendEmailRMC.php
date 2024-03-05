<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Users;

class ResendEmailRMC extends Controller3
{

    public $model = Users::class;

    public $log_event_id = 'Aktiváló e-mail';
    public $log_event_action = ['update' => 'újraküldés'];


    public $select = [
        '*',
    ];

    public function update(Request $request,$id){
        $model = $this->model::findOne($id);

        if (!$model) sendError('A rekord nem található!');
        $token = uniqid();
        $model->activation_token = $token;
        $model->created_by = getUserId();
        $model->save();

        sendMessage('user_activate', $model->email, [
            'token' => $token,
            'name' => $model->name,
            'email' => $model->email,
            'user_id' => $model->id,
            'link' => '<a href="' . config('app.url') . '#activate|' . $token . '" >Felhasználó aktiválás</a>'
        ]);

        return [];    }

}
