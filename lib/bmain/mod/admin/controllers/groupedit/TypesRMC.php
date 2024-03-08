<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use mod\admin\models\BNagycsoportTipus;

class TypesRMC extends Controller3
{

    public $model = BNagycsoportTipus::class;


    public $select = [
        'value' => 'b_nagycsoport_tipus_id',
        'name' => 'tipusnev',
    ];


    public function list(){
        return $this->defaultList();
    }


}