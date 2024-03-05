<?php

namespace mod\admin\controllers\entityedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mod\admin\models\UserEntities;
use mod\admin\models\Users;
use Illuminate\Support\Facades\Cache;

class UsersRMC extends Controller3
{

    public $log_event_id = 'Entitások / Felhasználók';


    public $model = Users::class;

    public $permissons = [
        'list' => true,
        'update' => ['SysAdmin','entity_admin'],
    ];

    public function list(Request $request)
    {
        return $this->defaultList([
            'id',
            'name',
            'email',
            'in_entity' => DB::raw('CASE WHEN EXISTS (SELECT Id FROM admin_user_entities WHERE admin_users.id = admin_user_entities.user_id AND admin_user_entities.entity_id = ' . $request->entity_id . ' ) THEN true ELSE false END')
        ]);
    }
    public $metaNoUpdate = true;

    public function update(Request $request, $id)
    {
        if($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');

        $model = UserEntities::where('user_id', $id)->where('entity_id', $request->entity_id)->one();
        if ($model) {
            $model->delete();
        } else {
            UserEntities::create([
                'user_id' => $id,
                'entity_id' => $request->entity_id
            ]);
        }
        Cache::forget('user_perms_' . $id);
        Cache::forget('user_entities_' . $id);
        return [];
    }
}
