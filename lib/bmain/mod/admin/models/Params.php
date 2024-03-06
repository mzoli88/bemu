<?php

namespace mod\admin\models;

use hws\rmc\Model;
use Illuminate\Support\Facades\Cache;

class Params extends Model
{
    protected $table = 'admin_params';

    public $timestamps = false;

    public function defaultCollection()
    {
        return $this->toArray();
    }

    static function cache()
    {
        $params = self::get()->mapWithKeys(function ($r) {
            return [$r->modul_azon . '/' . $r->key => $r->value];
        })->toArray();
        Cache::put('params', $params);
        return $params;
    }

    static function cacheBorderConfig($onlyIfNotExit = false)
    {
        if ($onlyIfNotExit && file_exists(base_path('storage/app/config.json'))) return;
        file_put_contents(base_path('storage/app/config.json'), json_encode([
            "name" => getParam('system_name', 'admin'),
            "system_users" => getParam('login_system_users', ''),
            "company" => getParam('login_company', ''),
            "informative_text" => getParam('login_informative_text', ''),
            "login_logo" => getParam('login_logo', ''),
            "user_inanctive_timeout" => (int)getParam('user_inanctive_timeout', 0),
            'smink' => [
                'title-background-color' => getParam('title-background-color', 'admin'),
                'title-color' => getParam('title-color', 'admin'),
                'higlight-text-color' => getParam('higlight-text-color', 'admin'),
                'button-higlight-color' => getParam('button-higlight-color', 'admin'),
                'main-size' => getParam('main-size', 'admin'),
                'button-size' => getParam('button-size', 'admin'),
                'main-family' => getParam('main-family', 'admin'),
            ]
        ]));
    }

    static function getCache()
    {
        return Cache::remember('params', null, function () {
            return self::cache();
        });
    }

    public function afterCreate()
    {
        Cache::forget('params');
    }

    public function afterUpdate()
    {
        Cache::forget('params');
    }

    protected $fillable = [
		"modul_azon",
        "key",
        "value",
    ];

    protected $casts = [
		"id" => "integer",
        "modul_azon" => "string",
        "key" => "string",
        "value" => "string",
    ];

    protected $validation = [
		"modul_azon" => "required|max:100",
        "key" => "required|max:100",
        "value" => "nullable",
    ];

    protected $labels = [
		"id" => "Azonosító",
    ];
}
