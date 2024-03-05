<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use mod\admin\models\UserPerms;
use mod\admin\models\Users;
use mod\templates\models\ModulUsers;

class PermsRMC extends Controller3
{

    public $model = Users::class;

    public $log_event_id = 'Jogosultságok';

    public $use_tarnsaction = [];

    public $permissons = [
        'list' => true,
        'update' => ['SysAdmin','users_admin','user_perms'],
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
            'perms' => UserPerms::where('user_id', $request->user_id)
                ->entity()
                ->all()
                ->mapWithKeys(function ($v, $k) {
                    return [$v->modul_azon . '#' . $v->perm => true];
                }),
            'templates' => ModulUsers::entity()->where('user_id',$request->user_id)->pluck('modul_azon'),
            'hastemplates' => collect(config('messageTypes'))->filter(function($v){return !empty($v);})->keys(),
        ];
    }

    public $metaNoUpdate = true;

    public function update(Request $request, $id)
    {
        if($id == getUserId() && !isSysAdmin()) sendError('Nem lehet a saját felhaszálónkhoz tartozó adatokat módosítani!');
        
        $model = UserPerms::where('user_id', $id)
            ->where('modul_azon', $request->modul_azon)
            ->where('perm', $request->perm)
            ->entity()->one();
        if ($model) {
            $model->delete();
        } else {
            UserPerms::create([
                'entity_id' => getEntity(),
                'user_id' => $id,
                'modul_azon' => $request->modul_azon,
                'perm' => $request->perm,
            ]);
        }
        Cache::forget('user_perms_' . $id);
        return [];
    }
}
