<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use mod\admin\models\BNagycsoport;

class GroupsRMC extends Controller3
{

    public $model = BNagycsoport::class;


    public $select = [
        'id' => 'b_nagycsoport_id',
         'nev',
        'b_nagycsoport_tipus_name' => 'BNagycsoportTipus.tipusnev',
        
    ];


    public function cols() {
        return [
            'nev' => true,
            'b_nagycsoport_tipus_name' => [
                'cu' => [
                    'type' => 'combo',
                    'store' => 'types',
                    'required' => true,
                    'name' => 'b_nagycsoport_tipus_id',

                ],
            ],


        ];
    }


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