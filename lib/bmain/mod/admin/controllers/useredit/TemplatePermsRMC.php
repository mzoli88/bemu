<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Users;
use mod\templates\models\ModulUsers;

class TemplatePermsRMC extends Controller3
{

    public $model = Users::class;

    public $log_event_id = 'Jogosultságok';

    public $use_tarnsaction = [];

    public $permissons = [
        'update' => ['SysAdmin','users_admin','user_perms'],
    ];

    public function update(Request $request, $id)
    {
        if($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');
        $model = ModulUsers::where('user_id', $id)
            ->where('modul_azon', $request->modul_azon)
            ->entity()->one();
        if ($model) {
            $model->delete();
        } else {
            ModulUsers::create([
                'entity_id' => getEntity(),
                'user_id' => $id,
                'modul_azon' => $request->modul_azon,
            ]);
        }
        return [];
    }
}
