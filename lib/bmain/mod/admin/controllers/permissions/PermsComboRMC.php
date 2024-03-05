<?php

namespace mod\admin\controllers\permissions;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\UserStates;

class PermsComboRMC extends Controller3
{

    public $model = UserStates::class;

    public $log_event_id = 'Jogosultságok / Felhasználó állapotok';


    public $select = [
        'name' => 'state',
        'value' => 'id'
    ];

    public function list(Request $request)
    {
        $data = [];
        $mods = config('mods');
        if ($request->parent_id && array_key_exists($request->parent_id, $mods)) {

            $modul = collect($mods[$request->parent_id]);

            $data = collect($modul['menu'])->map(function ($d, $azon) {
                return [
                    'value' => $azon,
                    'name' => $d['name'],
                ];
            })->sortBy('name');

            if ($modul->has('perms')) {
                $data = (collect($modul['perms'])->map(function ($d, $azon) {
                    return [
                        'value' => $azon,
                        'name' => $d,
                    ];
                })->merge($data)->sortBy('name')->values());
            }
        }

        return [
            'data' => $data
        ];
    }
}
