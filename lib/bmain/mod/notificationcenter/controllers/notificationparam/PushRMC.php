<?php

namespace mod\notificationcenter\controllers\notificationparam;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Params;

class PushRMC extends Controller3
{

    public $model = Params::class;

    public $log_event_id = 'Push paraméterek';
    public $log_event_action = ['create' => 'módosítás'];

    public $permissons = [
        'list' => true,
        'view' =>true,
        'create' => 'cnadmin',
        'update' => 'cnadmin',
        'delete' => 'cnadmin',
    ];

    public $select = [
        '*',
    ];

    protected $use_keys = [
        "push_fb_url_",
        "push_fb_key_",
        "push_fb_android_channel_id_",

        "proxy_use_",
        "proxy_host_",
        "proxy_port_",
        "proxy_user_",
        "proxy_pw_",
    ];

    public function list()
    {
        $params = getParams();
        $out = [];
        $entityId = getEntity();

        $keys = [];
        foreach($this->use_keys as $value){
            $keys[] = $value . $entityId;
        }
        foreach($params as $key =>  $val){
            if (in_array($key, $keys)){
                $_key = explode('_', $key);
                unset($_key[count( $_key) -1 ]);
                $out[implode('_', $_key) . '_'] = $val;
            }
        }
        return $out; 
    }

    public function create()
    {
        $params = request()->all();
        $newParams = [];
        $entityId = getEntity();

        foreach($params as $key => $value){
            if ($key == 'push_fb_key_'){
                if (!empty($value)){
                    $data = json_decode($value);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        sendError('Hibás JSON formátum a "Firebase json" mezőben!');
                    }

                    if (!is_dir(base_path() . '/mod/notificationcenter/push')){
                        mkdir(base_path() . '/mod/notificationcenter/push', 0777, true);
                    }
                    
                    file_put_contents(base_path() . '/mod/notificationcenter/push/push_' . $entityId . '.json', $value);
                }

            }
                
            $newParams[$key.$entityId] = $value;
        }

        setParams($newParams);

        return [];
    }

    public function update(Request $request, $id)
    {
        return $this->create();
    }
}
