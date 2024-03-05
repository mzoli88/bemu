<?php

namespace mod\admin\controllers\permissions;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Groups;

class GroupsRMC extends Controller3
{

    public $model = Groups::class;

    public $log_event_id = 'Jogosultságok / Szerepkörök';

    public $select = [
        'name',
        'value' => 'id',
    ];

    public function list(){
        return $this->defaultList();
    }

}
