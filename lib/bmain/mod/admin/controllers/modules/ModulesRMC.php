<?php

namespace mod\admin\controllers\modules;

use hws\rmc\Controller3;
use mod\admin\models\BModulok;
use mod\admin\models\Nevek;

class ModulesRMC extends Controller3
{
    public $model = BModulok::class;

    public $select = [
       
        'id' => 'b_modulok_id',
        "azon",
        "verzio",
        "modulnev",
    ];

    public function cols() {
        return [
            'b_modulok_id' => true,
            'azon' => [
                'update' => false,
            ],
            'verzio' => true,
            'modulnev' => [
                'update' => false,
            ],
        ];
    }
    public function list(){
        return $this->defaultList();
    }
    public function update(){
        return $this->defaultUpdate();
    }
}