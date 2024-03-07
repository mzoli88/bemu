<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\GroupPerms;
use mod\admin\models\UserGroups;
use mod\admin\models\Users;
use Illuminate\Support\Facades\Cache;
use mod\templates\models\ModulGroups;

class PermsRMC extends Controller3
{

    public $log_event_id = 'Szerepkörök / jogok';

    public $model = Users::class;

    public $use_tarnsaction = [];

    public $permissons = [
        'list' => true,
        'update' => ['SysAdmin', 'group_admin'],
    ];

    public function list(Request $request)
    {
        $mods = collect(config('mods'));
        unset($mods['start']);
        return [
            'data' => $mods->map(function ($r) {
                unset($r['dir']);
                unset($r['db_prefix']);
                unset($r['sort']);
                return $r;
            })->toArray(),
            'perms' => GroupPerms::where('group_id', $request->group_id)
                ->entity()->all()
                ->mapWithKeys(function ($v, $k) {
                    return [$v->modul_azon . '#' . $v->perm => true];
                }),

            'templates' => ModulGroups::entity()->where('group_id', $request->group_id)->pluck('modul_azon'),
            'hastemplates' => collect(config('messageTypes'))->filter(function ($v) {
                return !empty($v);
            })->keys(),
        ];
    }
    public $metaNoUpdate = true;

    public function update(Request $request, $id)
    {
        if (!isSysAdmin()) {
            $isInGroup =  UserGroups::where('group_id', $id)
                ->where('user_id', getUserId())
                ->one();
            if ($isInGroup) sendError('Nem lehet a szerepkörnél jogosultságot állítani, ha a saját felhasználónk szerepel benne!');
        }

        $model = GroupPerms::where('group_id', $id)
            ->where('modul_azon', $request->modul_azon)
            ->where('perm', $request->perm)
            ->entity()->one();
        if ($model) {
            $model->delete();
        } else {
            GroupPerms::create([
                'entity_id' => getEntity(),
                'group_id' => $id,
                'modul_azon' => $request->modul_azon,
                'perm' => $request->perm,
            ]);
        }

        UserGroups::where('group_id', $id)->get()->each(function ($UserGroup) {
            Cache::forget('user_perms_' . $UserGroup->user_id);
        });

        return [];
    }
}
