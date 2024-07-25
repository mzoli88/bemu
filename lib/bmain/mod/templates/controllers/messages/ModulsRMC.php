<?php

namespace mod\templates\controllers\messages;

use hws\rmc\Controller3;
use mod\admin\models\Entities;

class ModulsRMC extends Controller3
{

    public $model = Entities::class;
    
    public $log_event_id = 'Modulok';

    public function list()
    {
        $messageTypes = config('messageTypes');
        return [
            'data' => collect($messageTypes)->map(function ($r, $k) {
                return [
                    'name' => getModulName($k),
                    'value' => $k,
                ];
            })->sortBy('name')->values()->toArray()
        ];
    }
}
