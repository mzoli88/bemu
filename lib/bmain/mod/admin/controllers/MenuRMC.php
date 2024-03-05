<?php

namespace mod\admin\controllers;

use App\AuthController;
use hws\Border2;
use hws\rmc\Controller3;
use mod\admin\models\UserEntities;

class MenuRMC extends Controller3
{
    public $log_event_id = 'Menüpontok betöltése';
    public $log_event_action = ['list' => ''];

    public function list()
    {


        return Border2::getUserData();

        $is_sys_admin = isSysAdmin();
        // $menu_params = getParam('menu_params');
        // $menu_params = json_decode($menu_params, true);
        // if (empty($menu_params)) $menu_params = [];
        $menu_params = [];

        $entities = UserEntities::select(['value' => 'entity_id', 'name' => 'Rel_entity.name'])
            ->where('user_id', getUserId())->where('Rel_entity.status', 'I')
            ->orderBy('name')
            ->get();
        if ($entities->count() == 0){
            // AuthController::logout();
            sendError("Felhasználó nincsen entitáshoz kötve! Keressen fel egy rendszer adminisztrátort!");
        } 

        $perms = getUserPerms();

        $mods = $entities->mapWithKeys(function ($r) use ($perms, $menu_params, $is_sys_admin) {
            $entity_id = $r->value;
            return [$entity_id =>  collect(config('mods'))->filter(function ($v, $k) use ($perms, $entity_id, $is_sys_admin) {
                if ($k == 'start') return true;
                if ($is_sys_admin && $k == 'admin') return true;
                if (!array_key_exists($entity_id, $perms)) return false;
                return array_key_exists($k, $perms[$entity_id]);
            })->mapWithKeys(function ($r, $key) use ($menu_params) {
                if (array_key_exists('type_' . $key, $menu_params)) {
                    $i = 0;
                    return collect($r['menu'])->mapWithKeys(function ($v, $k) use ($key, $menu_params, &$i, $r) {
                        $sort = array_key_exists('sort', $r) ? $r['sort'] : 200;
                        if (array_key_exists('sort_' . $key, $menu_params)) {
                            $sort = $menu_params['sort_' . $key] + $i;
                            $i++;
                        }
                        $v['sort'] =  $sort;
                        return [$key . '.' . $k => $v];
                    });
                } else {
                    return  [
                        $key => [
                            'name' => $r['name'],
                            'sort' => array_key_exists('sort', $r) ? $r['sort'] : (array_key_exists('sort_' . $key, $menu_params) ? $menu_params['sort_' . $key] :   200),
                            'icon' => $r['icon'],
                            'version' => $r['version'],
                            'menu' => array_key_exists('menu', $r) ? $r['menu'] : null,
                        ]
                    ];
                }
            })->sortBy('sort')
            ->map(function ($v, $modul_azon) use ($perms, $entity_id, $is_sys_admin) {
                if ($modul_azon == 'start') return $v;
                if (!array_key_exists('menu', $v)) return $v;
                $v['menu'] = collect($v['menu'])->filter(function ($v, $perm) use ($perms, $entity_id, $modul_azon, $is_sys_admin) {
                    // dd($v, $perm, $perms[$entity_id][$modul_azon]);
                    if ($is_sys_admin && $modul_azon == 'admin') return true;
                    return array_key_exists($perm, $perms[$entity_id][$modul_azon]);
                })->toArray();
                return $v;
            })->toArray()];
        });


        return [
            'userData' => getUser(),
            // 'entities' => $entities,
            // 'active_entity' => $entities->first()->value,
            'active_entity' => 1,
            'perms' => $perms,
            'mods' => $mods,
            'sys_admin' => isSysAdmin(),
        ];
    }
}
