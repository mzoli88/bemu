<?php

namespace mod\admin\controllers;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ParamsRMC extends Controller3
{
    public $log_event_id = 'Pareméterek';
    public $log_event_action = ['create' => 'módosítás'];

    public $permissons = [
        'list' => true,
        'view' => true,
        'create' => ['systemparam_edit','SysAdmin'],
    ];

    public function list()
    {
        return getParams();
    }

    public function create(Request $request)
    {

        setParams($request->all());
        Cache::forget('log_debug');

        return [];
    }
}
