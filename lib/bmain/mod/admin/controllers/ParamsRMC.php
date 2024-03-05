<?php

namespace mod\admin\controllers;

use hws\rmc\Controller3;
use hws\UploadCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use mod\admin\models\Params;

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

        Cache::forget('log_debug');

        $params = request()->all();
        if($request->filled('login_logo') && is_array($request->login_logo)){
            $params['login_logo'] = 'SiteLogo.'. preg_replace('/.*\./','', $request->login_logo['name']);
            UploadCheck::runFromJson($request->login_logo,[
                'image/jpeg' => ['jpeg', 'jpg', 'jpe'],
                'image/png' => ['png'],
                'image/gif' => ['gif'],
            ]);
            // $file = new File('public/'.$params['login_logo'], base64_decode($request->login_logo["file_content"]));
            Storage::put('public/'.$params['login_logo'], base64_decode($request->login_logo["file_content"]));
        }
        setParams($params);
        
        Params::cacheBorderConfig();
        return [];
    }
}
