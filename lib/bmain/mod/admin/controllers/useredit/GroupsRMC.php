<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use mod\admin\models\Groups;
use mod\admin\models\UserGroups;
use Illuminate\Support\Facades\Cache;

class GroupsRMC extends Controller3
{

    public $model = Groups::class;

    public $log_event_id = 'Szerepkörök';

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
            'status',
            'in_group' => DB::raw('CASE WHEN EXISTS (SELECT Id FROM admin_user_groups WHERE admin_groups.id = admin_user_groups.group_id AND admin_user_groups.user_id = ' . $request->user_id . ' ) THEN true ELSE false END')
        ])->status()->entity();
    }

    public function cols()
    {
        return [
            'name' => true
        ];
    }

    public $metaNoUpdate = true;

    public function update(Request $request, $id)
    {
        if($request->user_id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');

        $model = UserGroups::where('group_id', $id)->where('user_id', $request->user_id)->one();
        if ($model) {
            $model->delete();
        } else {
            UserGroups::create([
                'group_id' => $id,
                'user_id' => $request->user_id
            ]);
        }
        Cache::forget('user_perms_' . $request->user_id);
        return [];
    }
}
