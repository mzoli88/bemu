<?php

namespace mod\admin\controllers\permissions;

use hws\rmc\Controller3;
use mod\admin\models\Entities;

class ModulsRMC extends Controller3
{

    public $model = Entities::class;

    public $log_event_id = 'Jogosultságok / Modulok';


    public function list()
    {
        $perms = getUserPerms();
        $entity_id = getEntity();
        $mods = config('mods');
        unset($mods['start']);
        // if (!array_key_exists($entity_id, $perms)) return [];
        return [
            'data' => collect($mods)->map(function ($r, $k) use ($mods) {
                return [
                    'name' => $r['name'],
                    'value' => $k,
                ];
            })->sortBy('name')->values()->toArray()
        ];
    }
}
