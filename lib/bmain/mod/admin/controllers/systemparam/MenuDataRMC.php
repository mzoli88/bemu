<?php

namespace mod\admin\controllers\systemparam;

use hws\rmc\Controller3;

class MenuDataRMC extends Controller3
{

    public $log_event_id = 'Pareméterek / Menüpontok';
    public $log_event_action = ['create' => 'beállítás'];


    public function list()
    {
        $menu_params = getParam('menu_params');
        $menu_params = json_decode($menu_params, true);
        if (empty($menu_params)) $menu_params = [];

        return collect(config('mods'))
            ->filter(function ($v, $key) {
                return $key != "start";
            })
            ->map(function ($r, $azon) use ($menu_params) {
                return [
                    'icon' => $r['icon'],
                    'name' => $r['name'],
                    'type' => array_key_exists('type_' . $azon, $menu_params) ? (int)$menu_params['type_' . $azon] : 1,
                    'sort' => array_key_exists('sort_' . $azon, $menu_params) ? (int)$menu_params['sort_' . $azon] : 100,
                ];
            })->toArray();
    }

    public function create()
    {
        setParams(['menu_params' => json_encode(collect(request()->all())->filter(function ($r, $k) {
            if (preg_match('/^type\_/', $k)) return $r == 2;
            if (preg_match('/^sort\_/', $k)) return $r >= 10 && $r <= 800;
        })->toArray())]);
        return [];
    }
}
