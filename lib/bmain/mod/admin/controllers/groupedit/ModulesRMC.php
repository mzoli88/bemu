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


    public $metaNoCreate = true;
    public $metaNoDelete = true;

    public $use_tarnsaction = [];


    public function list(Request $request)
    {
        $group_id = $request->group_id;
        return [
            'data' => collect(config('mods'))->map(function ($m, $modul_azon) use ($group_id) {
                $m = (object)$m;
                return [
                    'id' => $modul_azon,
                    'name' => $m->name,
                    'modul_azon' => $modul_azon,
                    'user_has' => BNagycsoportModul::where('modul_azon', $modul_azon)->where('b_nagycsoport_id', $group_id)->count(),
                ];
            })->values()->toArray()
        ];
    }

    public function create(request $request)
    {
        $model = BNagycsoportModul::make();
        $model->b_nagycsoport_id = $request->group_id;
        $model->modul_azon = $request->modul_azon;
        $model->save();
    }

    public function delete(request $request)
    {
        $model = BNagycsoportModul::where('b_nagycsoport_id', $request->group_id)->where('modul_azon', $request->modul_azon)->one();
        if (!$model) throw new \Exception('A rekord nem található!');
        $model->delete();
    }
}
