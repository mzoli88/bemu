<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use mod\admin\models\BNagycsoport;

class GroupsRMC extends Controller3
{

    public $model = BNagycsoport::class;

    // public $permissons = [
    //     'list' => ['badmin']
    // ];

    public $select = [
        '*'
    ];

    public function list(){
        return $this->defaultList();
    }

    public function create(){
        return $this->defaultCreate();
    }

    public function update(){
        return $this->defaultUpdate();
    }

}