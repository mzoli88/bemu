<?php
/*
    public $model = MobileUser::class;

    public $select = [
        'id',
        'name' => 'username',
    ];
    
    public function list(){
        return $this->defaultList();
    }

    public function view(){
        return $this->defaultView();
    }

    public function create(){
        return $this->defaultCreate();
    }

    public function update(){
        return $this->defaultUpdate();
    }

    public function delete(){
        return $this->defaultDelete();
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
use hws\xlsx\XlsxWriter;

class Controller2
{

    public $model;

    public $action;

    public $select = [];

    public $defaultSearch = [];

    public $tarnsaction_success = false;

    public $useFileUpload = false;

    public $use_tarnsaction = [
        'create', 'update', 'delete'
    ];

    public $log_event_id;
    public $log_event_action = [];

    public $log_chanels;

    public $lock = false;

    public $permissons;

    public $defaultSort;

    public $listCollection;
    public $metaList;
    public $metaCreate;
    public $metaUpdate;
    public $metaCreateUpdate;
    public $metaSearch;
    public $metaTitles;
    public $metaDetails;
    public $metaSmallDetaile;
    public $metaLinks;
    public $metaNoDownload = false;
    public $metaNoCreate = false;
    public $metaNoUpdate = false;

    public function getMeta(&$return)
    {
        if (!empty($this->defaultSearch)) {
            if (array_key_exists('defaultSearch', $return)) $this->defaultSearch = array_merge($return['defaultSearch'], $this->defaultSearch);
            $return['defaultSearch'] = $this->defaultSearch;
        }

        if ($this->useFileUpload) {
            $return['meta']['file_upload'] = [
                'type' => 'file',
                'title' => 'Csatolás',
                'search' => false,
                'validation' => 'required',
                'list' => false,
            ];
        }
        if ($this->metaList) foreach ($this->metaList as $key => $value) {
            $return['meta'][$key]['list'] = $value;
        }
        if ($this->metaCreateUpdate) foreach ($this->metaCreateUpdate as $key => $value) {
            $return['meta'][$key]['create'] = $value;
            $return['meta'][$key]['update'] = $value;
        }
        if ($this->metaCreateUpdate === false) foreach ($return['meta'] as $key => $value) {
            $return['meta'][$key]['create'] = false;
            $return['meta'][$key]['update'] = false;
        }
        if ($this->metaCreate) foreach ($this->metaCreate as $key => $value) {
            $return['meta'][$key]['create'] = $value;
        }
        if ($this->metaCreate === false) foreach ($return['meta'] as $key => $value) {
            $return['meta'][$key]['create'] = false;
        }
        if ($this->metaUpdate) foreach ($this->metaUpdate as $key => $value) {
            $return['meta'][$key]['update'] = $value;
        }
        if ($this->metaUpdate === false) foreach ($return['meta'] as $key => $value) {
            $return['meta'][$key]['update'] = false;
        }
        if ($this->metaSearch) foreach ($this->metaSearch as $key => $value) {
            $return['meta'][$key]['search'] = $value;
        }
        if ($this->metaTitles) foreach ($this->metaTitles as $key => $value) {
            $return['meta'][$key]['title'] = $value;
        }
        if ($this->metaDetails) foreach ($this->metaDetails as $key => $value) {
            $return['meta'][$key]['details'] = $value;
        }
        if ($this->metaSmallDetaile) foreach ($this->metaSmallDetaile as $key => $value) {
            $return['meta'][$key]['smallDetaile'] = $value;
        }

        if (method_exists($this, 'create') && $this->metaNoCreate == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['create'] = Border::getJog($this->permissons['all']);
                else if (array_key_exists('create', $this->permissons)) $return['create'] = Border::getJog($this->permissons['create']);
                else $return['create'] = false;
            } else $return['create'] = true;
        }

        if (method_exists($this, 'update') && $this->metaNoUpdate == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['update'] = Border::getJog($this->permissons['all']);
                else if (array_key_exists('update', $this->permissons)) $return['update'] = Border::getJog($this->permissons['update']);
                else $return['update'] = false;
            } else $return['update'] = true;
        }
        if (method_exists($this, 'delete')) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['delete'] = Border::getJog($this->permissons['all']);
                else if (array_key_exists('delete', $this->permissons)) $return['delete'] = Border::getJog($this->permissons['delete']);
                else $return['delete'] = false;
            } else $return['delete'] = true;
        }
        if (method_exists($this, 'export')) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['export'] = Border::getJog($this->permissons['all']);
                else if (array_key_exists('export', $this->permissons)) $return['export'] = Border::getJog($this->permissons['export']);
                else $return['export'] = false;
            } else $return['export'] = true;
        }
        if (method_exists($this, 'import')) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['import'] = Border::getJog($this->permissons['all']);
                else if (array_key_exists('import', $this->permissons)) $return['import'] = Border::getJog($this->permissons['import']);
                else $return['import'] = false;
            } else $return['import'] = true;
        }
        if (method_exists($this, 'download') && $this->metaNoDownload == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['download'] = Border::getJog($this->permissons['all']);
                else if (array_key_exists('download', $this->permissons)) $return['download'] = Border::getJog($this->permissons['download']);
                else $return['download'] = false;
            } else $return['download'] = true;
        }

        if (!empty($this->defaultSort)) {
            $return['sort'] = str_replace(['>', '<'], '', $this->defaultSort);
            $return['asc'] = !preg_match('/^\>/', $this->defaultSort);
        }

        if ($this->lock) {
            $return['lock'] = true;
        }

        if (is_array($this->useFileUpload)) {
            $return['meta']['file_upload']['create'] = [
                'accept' => implode(', ', $this->useFileUpload),
            ];
            $return['meta']['file_upload']['update'] = [
                'accept' => implode(', ', $this->useFileUpload),
            ];
        }
    }

    public function defaultList($select = null, $searchOverride = null, $Collection = null)
    {
        $this->listCollection = $Collection;
        return $this->model::query()->select($select ?: $this->select)->search($searchOverride ?: [])->showlock($this->lock)->sort();
    }


    public function defaultView($select = [], $id = null, $meargeSelect = true)
    {
        $id = $id ?: request()->route('id');
        // $query = $this->model::query();

        if ($meargeSelect) {
            $select = array_merge($this->select, $select);
        } else {
            $select = empty($select) ? $this->select : $select;
        }
        $model = $this->model::findOne($id, null, $select);
        if (!$model) throw new \Exception('A rekord nem található!');
        if ($this->lock) $model->lock();
        return $model;
    }

    public function defaultCreate($plusz_data = [], $plus_validation = [], $labels = [])
    {
        $model = $this->model::make();
        $data = $model->validate($plusz_data, $plus_validation, $labels);
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function defaultUpdate($id = null, $plusz_data = [], $plus_validation = [], $labels = [])
    {
        $id = $id ?: request()->route('id');
        $model = $this->model::findOne($id);
        if (!$model) throw new \Exception('A rekord nem található!');
        $data = $model->validate($plusz_data, $plus_validation, $labels);
        if ($this->lock) $model->lock();
        $model->fill($data);
        $model->save();
        return $model;
    }

    public function defaultDelete()
    {
        $id = request()->route('id');
        $model = $this->model::find($id);
        if (!$model) throw new \Exception('A rekord nem található!');
        if ($this->lock) $model->lock();
        $model->delete();
        return $model;
    }

    public function defaultExport($file_name, $headers = [], $q = false)
    {
        $file_name .= date('_YmdHis');
        if ($q == false) $q = call_user_func_array([$this, 'list'], [request()]);
        $meta = ['meta' => $q->getModel()->getMeta()];
        $this->getMeta($meta);
        $headers2 = collect($meta['meta'])->filter(function ($r, $key) {
            if (!array_key_exists('title', $r) || empty($r['title'])) return false;
            if (array_key_exists('list', $r) && $r['list'] == false) return false;
            if ($key == 'id') return false;
            return true;
        })->map(function ($r) {
            return $r['title'];
        })->merge($headers)->filter(function ($r) {
            return $r;
        })->toArray();
        $xlsx = new XlsxWriter($headers2);
        $xlsx->query($q, $this->listCollection);
        return $xlsx->download($file_name);
    }

    public function defaultUpload(Model $model)
    {
        if (!request()->has('file_upload')) return;
        $file = request()->file_upload;
        return $model->fileUpload($file);
    }

    public function defaultDeleteUpload(Model $model)
    {
        $model->fileDelete();
    }

    public function defaultDownload()
    {
        return $this->model::fileDownload();
    }

    static function checkClassName($name)
    {
        if ($name != '\\' . static::class) {
            throw new Exception("File name is different from class name! ('" . $name . "' != '" . '\\' . static::class . "') Maybe lowercase problem!", 1);
        }
    }

    public function callAction($method, $parameters)
    {
        $request = request();

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $header = $request->header('Content-type');
            if ($method == 'import') {
                if (!(preg_match('/^multipart\/form\-data/', $header) || $request->isJson())) {
                    return response([], 406);
                }
            } else
            if (!$request->isJson()) {
                return response([], 406);
            }
        }

        $ct_tmp = $request->getContent();

        if (!empty($ct_tmp)) {
            if (!preg_match('/^(\[|\{)/', $ct_tmp)) return response([], 406);
            unset($ct_tmp);
        }

        $this->action = $method;

        if (is_array($this->permissons)) {

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
            $return = call_user_func_array([$this, $method], array_values($parameters));
            if ($return instanceof EloquentBuilder) $return = $return->page($this->listCollection);
            if ($this->useFileUpload && $return instanceof Model) $this->defaultUpload($return);
            if ($this->useFileUpload && $return instanceof Model && $method == 'delete') $this->defaultDeleteUpload($return);
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
            if (array_key_exists('message', $return)) {
                $this->sendNaplo(null, $return['message']);
            } else {
                $this->sendNaplo();
            }
            $this->tarnsaction_success = true;
        }

        if (isset($_GET['getMeta']) && array_key_exists('meta', $return)) {
            $this->getMeta($return);
        }
        return $this->doCollection($return);
    }

    public function doCollection($data)
    {

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

    public function sendNaplo($err = null, $message = null)
    {
        if (!$this->log_event_id) return;
        hwslog($this->log_chanels, null, $message, $err);
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
