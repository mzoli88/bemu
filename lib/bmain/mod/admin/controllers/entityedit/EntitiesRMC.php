<?php

namespace mod\admin\controllers\entityedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use mod\admin\models\Entities;

class EntitiesRMC extends Controller3
{

    public $log_event_id = 'Entitások';

    public $model = Entities::class;

    public $permissons = [
        'list' => true,
        'create' => ['SysAdmin'],
        'update' => ['SysAdmin'],
    ];

    public function list()
    {
        return $this->defaultList();
    }

    public function create(Request $request)
    {
        Cache::forget('entities');
        $has = Entities::where('name',$request->name)->one();
        if($has) return response()->json([
            'name' => 'Ezzel a megnevezéssel már lett rögzíve entitás!'
        ], 422); 
        return $this->defaultCreate();
    }

    public function update(Request $request,$id)
    {
        Cache::forget('entities');
        $has = Entities::where('name',$request->name)->where('id','!=',$id)->one();
        if($has) return response()->json([
            'name' => 'Ezzel a megnevezéssel már lett rögzíve entitás!'
        ], 422); 
        return $this->defaultUpdate();
    }
}
