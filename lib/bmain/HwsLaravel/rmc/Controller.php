<?php
/*
    public $model = MobileUser::class;

    public $select = [
        'id',
        'name' => 'username',
    ];
    
    public function list(Request $request){
        return $this->model::query()->select($this->select)->search()->sort()->page();
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
        $model = $this->model::make();
        $data = $model->validate();
        $model->fill($data);
        $model->save();
        return ["success" => true];
    }

    public function update(Request $request,$id){
        $model = $this->model::find($id);
        if(!$model) throw new \Exception('A rekord nem található!');
        $data = $model->validate();
        $model->fill($data);
        $model->save();
        return ["success" => true];
    }

    public function delete(Request $request,$id){
        $model = $this->model::find($id);
        if(!$model) throw new \Exception('A rekord nem található!');
        $model->delete();
        return ["success" => true];
    }
    
*/

namespace hws\rmc;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use \Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use PDOException;
use hws\Border;
use Exception;
use hws\Web;

class Controller
{

    public $model;

    public $action;

    public $select = [];

    public $tarnsaction_success = false;

    public $use_tarnsaction = [
        'create', 'update', 'delete'
    ];

    public $log_event_id;

    public $log_chanels;

    static function checkClassName($name)
    {
        if ($name != '\\' . static::class) {
            throw new Exception("File name is different from class name! ('" . $name . "' != '" . '\\' . static::class . "') Maybe lowercase problem!", 1);
        }
    }

    public function callAction($method, $parameters)
    {
        $this->action = $method;

        if (property_exists($this, 'permissons')) {

            if (array_key_exists('all', $this->permissons)) {
                if (config('isBorder')) {
                    Border::checkJog($this->permissons['all']);
                } else {
                    Web::checkJog($this->permissons['all']);
                }
            } else {
                if (config('isBorder')) {
                    if (!array_key_exists($this->action, $this->permissons)) Border::checkJog(false);
                    Border::checkJog($this->permissons[$this->action]);
                } else {
                    if (!array_key_exists($this->action, $this->permissons)) Web::checkJog(false);
                    Web::checkJog($this->permissons[$this->action]);
                }
            }
        }

        if (in_array($this->action, $this->use_tarnsaction)) {
            DB::beginTransaction();
        }

        try {
            $return = call_user_func_array([$this, $method], $parameters);
        } catch (\Throwable $th) {
            report($th);
            if ($th instanceof ValidationException) $this->sendNaplo('Validációs hiba történt');
            elseif ($th instanceof PDOException) $this->sendNaplo('Adatbázis hiba történt');
            elseif (get_class($th) == 'Laravel\Passport\Exceptions\OAuthServerException') $this->sendNaplo('Sikertelen authentikáció');
            else  $this->sendNaplo('Szerver oldali hiba történt');
            throw $th;
        }

        if (config('error')) {
            $this->sendNaplo(config('error'));
        } else if (is_array($return) && array_key_exists('success', $return) && $return['success'] == false) {
            if (array_key_exists('message', $return)) {
                $this->sendNaplo($return['message']);
            } else {
                $this->sendNaplo('Szerver oldali hiba történt');
            }
        } else if ($return instanceof Response && $return->status() == 422) {
            $this->sendNaplo('Validációs hiba történt');
        } else {
            //napló, ha minden rendben van
            $this->sendNaplo();
            $this->tarnsaction_success = true;
        }
        return $this->doCollection($return);
    }

    public function doCollection($data)
    {

        if ($data instanceof EloquentBuilder) {
            return [
                'data' => $data->collect(),
            ];
        }

        if ($data instanceof Collection) {
            return [
                'data' => $data,
            ];
        }

        if ($data instanceof EloquentModel) {
            return [
                'data' => $data->collect(),
            ];
        }

        return $data;
    }

    public function sendNaplo($err = null)
    {
        if (!$this->log_event_id) return;
        hwslog($this->log_chanels, null, null, $err);
    }

    public function __destruct()
    {
        if (in_array($this->action, $this->use_tarnsaction)) {
            if ($this->tarnsaction_success) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        }
    }
}
