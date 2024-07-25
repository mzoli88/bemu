<?php

namespace mod\notificationcenter\controllers\notificationparam;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Params;

class SmtpRMC extends Controller3
{

    public $model = Params::class;

    public $log_event_id = 'E-mail paraméterek';
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
        "smtp_host_",
        "smtp_port_",
        "smtp_username_",
        "smtp_password_",
        "smtp_security_",
        "smtp_sender_name_",
        "smtp_sender_email_",
        "smtp_auth_",
        "smtp_authtype_"
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
