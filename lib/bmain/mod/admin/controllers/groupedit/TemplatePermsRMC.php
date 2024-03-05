<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Users;
use mod\templates\models\ModulGroups;

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
        $model = ModulGroups::where('group_id', $id)
            ->where('modul_azon', $request->modul_azon)
            ->entity()->one();
        if ($model) {
            $model->delete();
        } else {
            ModulGroups::create([
                'entity_id' => getEntity(),
                'group_id' => $id,
                'modul_azon' => $request->modul_azon,
            ]);
        }
        return [];
    }
}
