<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use mod\admin\models\BModulok;


class GroupsRMC extends Controller3
{

    public $model = BModulok::class;


    public $select = [
      

        
    ];


    public function cols() {
        return [
         
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