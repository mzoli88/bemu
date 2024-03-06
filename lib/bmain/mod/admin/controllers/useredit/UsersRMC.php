<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller3;
use mod\admin\models\Nevek;

class UsersRMC extends Controller3
{
    public $model = Nevek::class;

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
        return $this->defaultCreate([
            'belephet' => 1,
            'jelszo' => 1,
        ]);
    }

    public function update(){
        return $this->defaultUpdate();
    }

}
