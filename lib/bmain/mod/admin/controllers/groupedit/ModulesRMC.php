<?php

namespace mod\admin\controllers\groupedit;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use mod\admin\models\Nevek;
use Illuminate\Support\Facades\DB;
use mod\admin\models\BModulok;
use mod\admin\models\BNagycsoportModul;

class ModulesRMC extends Controller3
{


    public $model = BModulok::class;

    public $metaNoCreate = true;
    public $metaNoDelete = true;

    public $use_tarnsaction = [];


    public function list(Request $request)
    {
        return $this->defaultList([
            '*',
            "user_has" => DB::raw('SELECT count(*) FROM b_nagycsoport_modul WHERE b_nagycsoport_modul.modul_azon = b_modulok.azon AND b_nagycsoport_modul.b_nagycsoport_id = ' . $request->group_id),
        ]);
    }

    public function create(request $request)
    {
        $model = BNagycsoportModul::make();
        $model->b_nagycsoport_id = $request->group_id;
        $model->modul_azon = $request->azon;
        $model->save();
    }

    public function delete(request $request, $id)
    {
        $modul_azon = BModulok::findOne($id)->azon;
        $model = BNagycsoportModul::where('b_nagycsoport_id', $request->group_id)->where('modul_azon', $modul_azon)->one();
        if (!$model) throw new \Exception('A rekord nem található!');
        $model->delete();
    }
}
