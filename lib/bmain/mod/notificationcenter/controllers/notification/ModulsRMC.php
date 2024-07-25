<?php

namespace mod\notificationcenter\controllers\notification;

use hws\rmc\Controller3;

class ModulsRMC extends Controller3
{

    public $log_event_id = 'Modul';

    public function list()
    {
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
