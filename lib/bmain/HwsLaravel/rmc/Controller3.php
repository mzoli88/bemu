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

use App\Exceptions\SendErrorException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use \Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use hws\xlsx\XlsxWriter;
use PDOException;
use Exception;
use hws\xlsx\XlsxReader;
use Illuminate\Http\JsonResponse;

class Controller3
{

    public $model;

    public $action;

    public $select = [];


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
    public $defaultSearch = [];

    public $listCollection;


    public $metaNoCreate = false;
    public $metaNoUpdate = false;
    public $metaNoDelete = false;
    public $metaNoExport = false;
    public $metaNoImport = false;
    public $metaNoDownload = false;

    public function getMeta(&$return)
    {
        $return['cols'] = $this->cols();

        if (!empty($this->defaultSearch)) {
            if (array_key_exists('defaultSearch', $return)) $this->defaultSearch = array_merge($return['defaultSearch'], $this->defaultSearch);
            $return['defaultSearch'] = $this->defaultSearch;
        }

        if ($this->useFileUpload) {
            $return['cols']['file_upload'] = [
                'search' => false,
                'list' => false,
                'details' => false,
                'smallDetaile' => false,
                'create' => [
                    'type' => 'file',
                    'label' => 'Csatolás',
                    'validation' => 'required',
                ],
                'update' => [
                    'type' => 'file',
                    'label' => 'Csatolás',
                    'validation' => '',
                ],
            ];

            if (is_array($this->useFileUpload)) {
                $return['cols']['file_upload']['create']['accept'] = implode(', ', array_keys($this->useFileUpload));
                $return['cols']['file_upload']['update']['accept'] = implode(', ', array_keys($this->useFileUpload));
            }
        }

        if (method_exists($this, 'create') && $this->metaNoCreate == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['create'] = hasPerm($this->permissons['all']);
                else if (array_key_exists('create', $this->permissons)) $return['create'] = hasPerm($this->permissons['create']);
                else $return['create'] = false;
            } else $return['create'] = true;
        }

        if (method_exists($this, 'update') && $this->metaNoUpdate == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['update'] = hasPerm($this->permissons['all']);
                else if (array_key_exists('update', $this->permissons)) $return['update'] = hasPerm($this->permissons['update']);
                else $return['update'] = false;
            } else $return['update'] = true;
        }
        if (method_exists($this, 'delete') && $this->metaNoDelete == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['delete'] = hasPerm($this->permissons['all']);
                else if (array_key_exists('delete', $this->permissons)) $return['delete'] = hasPerm($this->permissons['delete']);
                else $return['delete'] = false;
            } else $return['delete'] = true;
        }
        if (method_exists($this, 'export') && $this->metaNoExport == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['export'] = hasPerm($this->permissons['all']);
                else if (array_key_exists('export', $this->permissons)) $return['export'] = hasPerm($this->permissons['export']);
                else $return['export'] = false;
            } else $return['export'] = true;
        }
        if (method_exists($this, 'import') && $this->metaNoImport == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['import'] = hasPerm($this->permissons['all']);
                else if (array_key_exists('import', $this->permissons)) $return['import'] = hasPerm($this->permissons['import']);
                else $return['import'] = false;
            } else $return['import'] = true;
        }
        if (method_exists($this, 'download') && $this->metaNoDownload == false) {
            if (is_array($this->permissons)) {
                if (array_key_exists('all', $this->permissons)) $return['download'] = hasPerm($this->permissons['all']);
                else if (array_key_exists('download', $this->permissons)) $return['download'] = hasPerm($this->permissons['download']);
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
    }

    public function cols()
    {
        return [];
    }

    public function defaultList($select = null, $searchOverride = null, $Collection = null)
    {
        $this->listCollection = $Collection;
        if (request()->route('id')) return $this->defaultView($select ?: $this->select, null);
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
        if (!in_array('*', $select)) $select[] = '*'; //entitás miatt nagyon kell
        $model = $this->model::findOne($id, null, $select);
        if (!$model) sendError('A rekord nem található!');

        $tmp = explode('.', $model::$entity_property);
        $entity_property = array_pop($tmp);
        $entity_rel = implode('.', $tmp);

        if ($entity_rel) {
            if ($model->{$entity_rel}) {
                if (!in_array(getEntity(), $model->{$entity_rel}->pluck($entity_property)->toArray())) sendError('A rekord másik entitáshoz tartozik!');
            }
        } else {
            if ($model->{$entity_property} && $model->{$model::$entity_property} != getEntity()) sendError('A rekord másik entitáshoz tartozik!');
        }
        if ($this->lock) $model->lock();
        return $model;
    }

    public function defaultCreate($plusz_data = [], $plus_validation = [], $labels = [])
    {
        $model = $this->model::make();
        $data = $model->validate($plusz_data, $plus_validation, $labels);
        $model->fill($data);

        $this->updateFileColumn($model);

        $model->save();
        return $model;
    }

    public function defaultUpdate($id = null, $plusz_data = [], $plus_validation = [], $labels = [])
    {
        $id = $id ?: request()->route('id');
        $model = $this->model::findOne($id);
        if (!$model) sendError('A rekord nem található!');
        $data = $model->validate($plusz_data, $plus_validation, $labels);
        if ($this->lock) $model->lock();
        $model->fill($data);

        $this->updateFileColumn($model);

        $model->save();
        return $model;
    }


    protected function updateFileColumn(Model $model)
    {
        if ($this->useFileUpload && request()->filled('file_upload')) {
            $file = request()->file_upload;
            if ($model->getLabels('file_name')) return $model->file_name = $file['name'];
            if ($model->getLabels('filename')) return $model->filename = $file['name'];
            if ($model->getLabels('fileName')) return $model->fileName = $file['name'];
            if ($model->getLabels('file')) return $model->file = $file['name'];
            if ($model->getLabels('name')) return $model->name = $file['name'];
            if ($model->getLabels('fajlnev')) return $model->fajlnev = $file['name'];
        }
    }

    public function defaultDelete()
    {
        $id = request()->route('id');
        $model = $this->model::find($id);
        if (!$model) sendError('A rekord nem található!');
        if ($this->lock) $model->lock();
        $model->delete();
        return $model;
    }

    public function defaultExport($file_name, $headers = [], $q = false, $collection = null, $noOrderBy = false, $noPage = false)
    {
        if ($q == false) $q = call_user_func_array([$this, 'list'], [request()]);

        if (empty($headers)) $headers = $this->getExportHeadersFromCols($q);

        $xlsx = new XlsxWriter();
        $xlsx->query($q, $headers, $collection ?: $this->listCollection, 2000, $noOrderBy, $noPage);
        return $xlsx->download($file_name);
    }

    public function defaultImport($rowEdit = null, $validator = null, $headers = [])
    {
        
        if (empty($headers)) {
            $q = call_user_func_array([$this, 'list'], [request()]);
            $headers = $this->getExportHeadersFromCols($q);
        }

        $model = $this->model;

        if(!$rowEdit && $model) $rowEdit = function($rowdata) use ($model){
            $model::create($rowdata);
        };

        $xlsx = new XlsxReader();
        $xlsx->import($rowEdit, $headers, $validator);
        return ['import'=>true];
    }

    public function defaultUpload(Model $model)
    {
        if (!request()->filled('file_upload')) return;
        $file = request()->file_upload;
        return $model->fileUpload($file, is_array($this->useFileUpload) ? $this->useFileUpload : null);
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

        if (is_callable('getMenu') && !hasPerm(call_user_func('getMenu') || true)) return response()->json(["message" => "Nincs Jogosultság!"], 403);

        if (is_array($this->permissons)) {
            if (array_key_exists('all', $this->permissons)) {
                if (!hasPerm($this->permissons['all'])) return response()->json(["message" => "Nincs Jogosultság!"], 403);
            } else {
                if (!array_key_exists($this->action, $this->permissons)) return response()->json(["message" => "Nincs Jogosultság!"], 403);
                if (!hasPerm($this->permissons[$this->action])) return response()->json(["message" => "Nincs Jogosultság!"], 403);
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
            elseif ($th instanceof SendErrorException) $this->sendNaplo(($th->getTitle()) ? $th->getTitle() . ', ' . $th->getMessage() : $th->getMessage());
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
        } else if (($return instanceof Response || $return instanceof JsonResponse) && $return->status() == 422) {
            $this->sendNaplo('Validációs hiba történt');
        } else if (($return instanceof Response || $return instanceof JsonResponse) && $return->status() != 200) {
            $this->sendNaplo('Szerver oldali hiba történt');
        } else {
            //napló, ha minden rendben van
            if (is_array($return) && array_key_exists('message', $return)) {
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
        if (empty($data)) return [];

        if ($data instanceof Collection) {
            return [
                'data' => $data,
            ];
        }

        if ($data instanceof EloquentModel) {
            return $data->collect();
        }

        return $data;
    }

    public function getExportHeadersFromCols($q)
    {
        $meta = ['meta' => $q->getModel()->getMeta()];
        $this->getMeta($meta);

        $cols = $this->cols();

        if (empty($cols)) $cols = $meta['meta'];

        return collect($cols)->filter(function ($r) {
            if ($r == false) return false;
            if (array_key_exists('list', $r) && $r['list'] == false) return false;
            return true;
        })->map(function ($v, $k) use ($meta) {
            $title2 = array_key_exists($k, $meta['meta']) && array_key_exists('title', $meta['meta'][$k]) ? $meta['meta'][$k]['title'] : 'Title hiányzik';
            if (!is_array($v) && $v == true) return $title2;
            if (array_key_exists('title', $v)) return $v['title'];
            return $title2;
        })->toArray();
    }

    public function sendNaplo($err = null, $message = null)
    {
        // if (!$this->log_event_id) return;
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
