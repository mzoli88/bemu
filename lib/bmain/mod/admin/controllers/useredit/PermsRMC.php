<?php

namespace mod\admin\controllers\useredit;

use hws\rmc\Controller;
use Illuminate\Http\Request;
use mod\admin\models\Csoport;
use mod\admin\models\NevekCsoportosit;
use Illuminate\Support\Facades\DB;


class PermsRMC extends Controller
{

    public $model = Csoport::class;

    public function list(Request $request){
        return $this->model::query()->select([
            '*',
            "user_has" => DB::raw('SELECT count(*) FROM nevek_csoportosit WHERE nevek_csoportosit.csoport_id = nevek_csoport.csoport_id AND nevek_csoportosit.nevek_id = '.$request->user_id),
        ])->search()->sort()->page();
    }

    public function view(Request $request,$id){
        $query = $this->model::query();
        $query->where($query->getModel()->getKeyName(),$id)->select($this->select);
        $model = $query->first();
        // $model = $this->model::find($id);
        if(!$model) throw new \Exception('A rekord nem található!');
        return $model;
    }

    public function create(Request $request){
        if($request->csoport_id){
            $model = NevekCsoportosit::make();
            $model->csoport_id = $request->csoport_id;
            $model->nevek_id = $request->user_id;
            $model->save();
        }else{
            foreach(Csoport::get() as $jog){
                $model = NevekCsoportosit::where('nevek_id',$request->user_id)->where('csoport_id',$jog->getKey())->one();
                if(!$model) $model = new NevekCsoportosit;
                $model->csoport_id = $jog->getKey();
                $model->nevek_id = $request->user_id;
                $model->save();
            }
        }
        return ["success" => true];
    }

    public function delete(Request $request,$id = null){
        if($id){
            $model = NevekCsoportosit::where('nevek_id',$request->user_id)->where('csoport_id',$id)->one();
            if(!$model) throw new \Exception('A rekord nem található!');
            $model->delete();
        }else{
            foreach( NevekCsoportosit::where('nevek_id',$request->user_id)->get() as $jog){
                $jog->delete();
            }
        }
        return ["success" => true];
    }

}
