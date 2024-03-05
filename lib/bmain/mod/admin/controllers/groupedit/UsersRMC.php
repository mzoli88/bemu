<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mod\admin\models\UserGroups;
use mod\admin\models\Users;

class UsersRMC extends Controller3
{

    public $log_event_id = 'Szerepkörök / felhasználók';

    public $model = Users::class;

    public $permissons = [
        'list' => true,
        'update' => ['SysAdmin','group_admin'],
    ];

    public $use_tarnsaction = [];


    public function list(Request $request)
    {
        return $this->defaultList([
            'id',
            'name',
            'email',
            'in_group' => DB::raw('CASE WHEN EXISTS (SELECT Id FROM admin_user_groups WHERE admin_users.id = admin_user_groups.user_id AND admin_user_groups.group_id = ' . $request->group_id . ' ) THEN true ELSE false END')
        ])->entity();
    }
    public $metaNoUpdate = true;

    public function update(Request $request, $id)
    {
        if($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');

        $model = UserGroups::where('user_id', $id)->where('group_id', $request->group_id)->one();
        if ($model) {
            $model->delete();
        } else {
            $model = UserGroups::create([
                'user_id' => $id,
                'group_id' => $request->group_id
            ]);
        }
        return [];
    }
}
