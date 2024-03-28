<?php

namespace hws\rmc;

use Illuminate\Database\Eloquent\Model as Emodel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use hws\Excel;
use Illuminate\Support\Facades\Storage;
use DateTimeInterface;
use hws\UploadCheck;
use Illuminate\Support\Facades\Cache;

class Model extends Emodel
{

    static $lastSelectModel;
    static $searchLog;

    static $modifyLog = [];

    static $safeHtml = []; //ha html property-t biztonságosra szeretnénk állítani. Inline kódokat törli 

    protected $validation = [];

    protected $labels = [];

    public $selectHelper = [];

    public $relationModels = [];
    public $relationArch = [];
    public $localJoinNames = [];
    public $defaultSearch = [];

    //ha a tábla karakterkódolása lati akkor true
    static $latin = false;
    static $useStatus = false;
    //HA van olyan property amire latinul kell keresni
    static $latinSearch = [];

    //entitás azonosító neve
    static $entity_property = 'entity_id';

    public $meta = [];

    //segéd latin kapcsoló, hogy tudja a model, hogy van latin karakterkódolású relation
    public $latinRelation = false;
    public $showlock = false;

    //Alapértelmezetten status = I -re keres ha van státusz kezelés
    // public function newQuery($excludeDeleted = true)
    // {
    //     $q = parent::newQuery($excludeDeleted);
    //     if (static::$useStatus) $q->where($this->getTable() . '.status', 'I');
    //     return $q;
    // }

    public function isPostgre()
    {
        return config('database.default') == "pgsql";
    }

    public function getSearch($prop = null)
    {
        if ($prop) {
            if (!array_key_exists($prop, $this->casts)) return NULL;
            return $this->casts[$prop];
        }
        return $this->casts;
    }

    public function getValidation($prop = null)
    {
        if ($prop) {
            if (!array_key_exists($prop, $this->validation)) return NULL;
            return $this->validation[$prop];
        }
        return collect($this->validation)->filter(function ($valid, $key) {
            return in_array($key, $this->fillable);
        })->toArray();
    }

    public function getLabels($prop = null)
    {
        if ($prop) {
            if (!array_key_exists($prop, $this->labels)) return NULL;
            return $this->labels[$prop];
        }
        return $this->labels;
    }

    public function scopeW($query, $property_name, $value, $value2 = '##titkos##')
    {
        if (!str_contains('.', $property_name)) $property_name = $query->getModel()->getTable() . '.' . $property_name;
        if ($value2 != '##titkos##') {
            if (empty($value2)) return $query;
            return $query->where($property_name, $value, $value2);
        } else {
            if (empty($value)) return $query;
            return $query->where($property_name, $value);
        }
    }
    public function scopeShowlock($query, $lock = true)
    {
        if ($lock) $this->showlock = true;
        return $query;
    }

    public function scopeFindOne($query, $id_or_propName, $value = null, $select = null)
    {
        if ($select) $query->select($select);
        if ($value) {
            $query->where($id_or_propName, $value);
        } else {
            $query->where($query->getModel()->getTable() . '.' . $query->getModel()->getKeyName(), $id_or_propName);
        }
        return $query->one();
    }

    public function scopeStatus($query)
    {
        $query->where($this->getTable() . '.status', 'I');
        return $query;
    }

    public function isLocked()
    {
        $cname = 'ModelLock_' . getModulAzon() . '_' . class_basename($this) . '_' . $this->getKey();
        if (Cache::has($cname)) {
            if (config('isBorder')) {
                $user = DB::table('nevek')->where('id', Cache::get($cname))->get()->first();
                return toUtf($user->teljesnev);
            } else {
                $user = DB::table('admin_users')->where('id', Cache::get($cname))->get()->first();
                return $user->name;
            }
        }
        return false;
    }

    public function lock()
    {
        $cname = 'ModelLock_' . getModulAzon() . '_' . class_basename($this) . '_' . $this->getKey();
        if (isset($_GET['doModelLockOpen'])) {
            if (Cache::has($cname) && Cache::get($cname) == getUserId()) Cache::forget($cname);
            response()->json([], 200)->send();
            exit;
        }
        if (Cache::has($cname)) {
            if (Cache::get($cname) == getUserId()) {
                Cache::add($cname, getUserId(), now()->addMinutes(5));
            } else {
                if (config('isBorder')) {
                    $user = DB::table('nevek')->where('id', Cache::get($cname))->get()->first();
                    response()->json([
                        'title' => "Hozzáférés nem engedélyezett!",
                        'message' => toUtf($user->teljesnev) . " - felhasználó már zárolta a rekordot!",
                    ], 500)->send();
                } else {
                    $user = DB::table('admin_users')->where('id', Cache::get($cname))->get()->first();
                    response()->json([
                        'title' => "Hozzáférés nem engedélyezett!",
                        'message' => $user->name . " - felhasználó már zárolta a rekordot!",
                    ], 500)->send();
                }
                exit;
            }
        } else {
            Cache::add($cname, getUserId(), now()->addMinutes(5));
        }
    }

    public function unLock()
    {
        $cname = 'ModelLock_' . getModulAzon() . '_' . class_basename($this) . '_' . $this->getKey();
        Cache::forget($cname);
    }

    static function create(array $props, $forceFill = false)
    {
        if (empty($props)) return false;

        if (is_array(array_values($props)[0])) {
            self::insert($props);
        } else {
            $model = get_called_class()::make();
            if ($forceFill) {
                $model->forceFill($props);
            } else {
                $model->fill($props);
            }
            $model->save();
            return  $model;
        }
    }

    public function scopeSelect($query, $selection = null)
    {
        if ($selection instanceof Expression) {
            $selection = ['ct' => $selection];
        }
        if (empty($selection) || !is_array($selection)) return;
        $qb = $query->getQuery();
        $table_name = $query->getModel()->getTable();
        $this->selectHelper = [];
        // $this->relationModels = [];
        $select = [];
        static::$lastSelectModel = $this;

        foreach ($selection as $key => $value) {
            if ($value instanceof Expression) {
                //Laravel 10 else 9 és kisebb
                if (method_exists(DB::connection(), 'getQueryGrammar')) {
                    $qgrammar =  DB::connection()->getQueryGrammar();
                    $select[] = DB::raw('(' . $value->getValue($qgrammar) . ') as ' . $key);
                    $this->selectHelper[$key] = DB::raw('(' . $value->getValue($qgrammar) . ')');
                } else {
                    $select[] = DB::raw('(' . call_user_func([$value, 'getValue']) . ') as ' . $key);
                    $this->selectHelper[$key] = DB::raw('(' . call_user_func([$value, 'getValue']) . ')');
                }
                continue;
            }
            if (is_array($value)) {
                $val = collect($value)->map(function ($r, $i) use ($query) {
                    if ($i == 0) return "'" . $r . "'";

                    // dd($r, strpos($r, '"') === false && strpos($r, "'") === false);

                    if (strpos($r, '"') === false && strpos($r, "'") === false) {

                        $arr = explode('.', $r);
                        array_pop($arr);

                        if (!empty($arr)) {
                            $query->join(implode('.', $arr));
                        }
                    }
                    return $r;
                })->implode(', ');
                $select[] = DB::raw("CONCAT_WS(" . $val . ")  as " . $key);
                $this->selectHelper[$key] = DB::raw("CONCAT_WS(" . $val . ")");
                continue;
            }
            $arr = explode('.', $value);
            $prop = array_pop($arr);
            $can_search_or_filter = $prop != '*';

            if (!empty($arr)) {
                if (!$can_search_or_filter) {
                    $query->with(implode('.', $arr));
                    continue;
                }
                $query->join(implode('.', $arr));
                $prop_full = array_pop($arr) . '.' . $prop;
            } else {
                $prop_full = $table_name . '.' . $prop;
            }
            if (is_numeric($key)) {
                $select[] = $prop_full;
                if ($can_search_or_filter) $this->selectHelper[$prop] = $prop_full;
            } else {
                $select[] = $prop_full . ' as ' . $key;
                if ($can_search_or_filter) $this->selectHelper[$key] = $prop_full;
            }
        }

        if (in_array('*', $selection) || in_array($table_name . '.' . '*', $selection)) {
            $plus_sdata = $this->getSearch();
            if (!empty($plus_sdata)) {
                $Plus_array = [];
                foreach (array_keys($plus_sdata) as $sprop) {
                    $Plus_array[$sprop] = $table_name . '.' . $sprop;
                }
                $this->selectHelper = array_merge($Plus_array, $this->selectHelper);
            }
        }
        $qb->select($select);
        return $query;
    }

    public function scopeJoin($query, $rel)
    {

        $args = func_get_args();

        $qb = $query->getQuery();

        if (count($args) === 5) {
            array_shift($args);
            $qb->join($args[0], $args[1], $args[2], $args[3]);
            return $query;
        }

        if ($rel) {
            $tmp_rel = explode('.', $rel);
            $next_model = $query->getModel();
            $next_alias = $next_model->getTable();
            $rel_arch = [];
            foreach ($tmp_rel as $f_alias) {
                if (!method_exists($next_model, $f_alias)) sendError($f_alias . "- relation does not exist in model");

                $relation = call_user_func_array([$next_model, $f_alias], []);

                $f_table = $relation->getRelated()->getTable();
                $join_tables = $f_table . ' as ' . $f_alias;

                if (!array_key_exists($f_alias, $this->relationModels)) {

                    $this->relationModels[$f_alias] = $relation->getRelated();
                    if ($this->relationModels[$f_alias]::$latin) {
                        $this->doLatinRelation();
                    }

                    // HasOne
                    if ($relation instanceof HasOne) {
                        $tmp = explode('.', $relation->getQualifiedParentKeyName());
                        $fkname = $f_alias . '.' . $relation->getForeignKeyName();
                        $lkname = $next_alias . '.' . end($tmp);
                        // $qb->join($join_tables,$lkname,'=',$fkname);
                        $qb->leftJoin($join_tables, $lkname, '=', $fkname);
                    } else

                    if ($relation instanceof BelongsTo) {
                        $fkname = $f_alias . '.' . $relation->getOwnerKeyName();
                        $lkname = $next_alias . '.' . $relation->getForeignKeyName();
                        // $qb->join($join_tables,$lkname,'=',$fkname);
                        $qb->leftJoin($join_tables, $lkname, '=', $fkname);
                    } else

                    if ($relation instanceof HasMany) {
                        $tmp = explode('.', $relation->getQualifiedParentKeyName());
                        $fkname = $f_alias . '.' . $relation->getForeignKeyName();
                        $lkname = $next_alias . '.' . end($tmp);
                        // $qb->join($join_tables,$lkname,'=',$fkname);
                        $qb->leftJoin($join_tables, $lkname, '=', $fkname);
                    } else

                    if ($relation instanceof BelongsToMany) {
                        $fkname = $f_alias . '.' . $relation->getOwnerKeyName();
                        $lkname = $next_alias . '.' . $relation->getForeignKeyName();
                        // $qb->join($join_tables,$lkname,'=',$fkname);
                        $qb->leftJoin($join_tables, $lkname, '=', $fkname);
                    }

                    if ($lkname) $this->localJoinNames[] = $lkname;
                }

                $next_alias = $f_alias;
                $next_model = $relation->getRelated();
                $rel_arch[] = $next_alias;
                $this->relationArch[$f_alias] = implode('.', $rel_arch);
            }
        }
        return $query;
    }

    public function scopeSearch($query, array $override = [], array $defaultSearch = [])
    {
        // $this->defaultSearch = $defaultSearch;
        $basemodel = $query->getModel();
        $basetable = $basemodel->getTable();
        $this->relationModels[$basetable] = $basemodel;
        // $request = app('request'); // nem használjuk a requestet, mert a 0 értékre NULL - értéket ad vissza
        if (empty($this->selectHelper)) {
            $plus_sdata = $this->getSearch();
            if (!empty($plus_sdata)) {
                $this->selectHelper = [];
                foreach (array_keys($plus_sdata) as $sprop) {
                    $this->selectHelper[$sprop] = $basetable . '.' . $sprop;
                }
            }
        }

        $shelper = array_merge($this->selectHelper, $override);
        // dd($shelper);
        if (empty($shelper) || empty($_GET)) return;

        static::$searchLog = [];

        foreach ($shelper as $find => $search_property) {
            if (array_key_exists($find, $_GET) || array_key_exists($find . '>', $_GET) || array_key_exists($find . '<', $_GET)) {

                if (is_callable($search_property)) {
                    if (!array_key_exists($find, $_GET)) continue;
                    // dd($search_property);
                    $value = $_GET[$find]; //$request->query($find);
                    if (is_callable($override[$find])) {
                        if (preg_match('/^\[.*\]$/', $value)) $value = json_decode($value, true);
                        $override[$find]($query, $value);
                        continue;
                    }
                    if (is_array($override[$find])) {
                        $query->where($search_property, $override[$find][0], array_key_exists(2, $override[$find]) ? $override[$find][1] : $value);
                        static::$searchLog[] = [$search_property, $override[$find][0], array_key_exists(2, $override[$find]) ? $override[$find][1] : $value];
                    } else {
                        $query->where($search_property, "=", $override[$find]);
                        static::$searchLog[] = [$search_property, "=", $override[$find]];
                    }
                    continue;
                }

                $relmodel = $basemodel;

                if ($search_property instanceof Expression) {


                    if (method_exists(DB::connection(), 'getQueryGrammar')) {
                        $qexpressionValue = $search_property->getValue(DB::connection()->getQueryGrammar());
                    } else {
                        $qexpressionValue = call_user_func([$search_property, 'getValue']);
                    }

                    if (preg_match('/^CONCAT/', $qexpressionValue)) {
                        $type = 'string';
                    } else {
                        $type = $basemodel->getSearch($find) ?: 'integer';
                    }
                    $search_name = $find;
                } else {
                    $tmp = explode('.', $search_property);
                    $prop = array_pop($tmp);
                    $rel = implode('.', $tmp);
                    $type = 'integer';

                    if (array_key_exists($rel, $this->relationModels)) {
                        $relmodel = $this->relationModels[$rel];
                        $type = $relmodel->getSearch($prop) ?: 'integer';
                    }
                    $search_name = $find . ' (' . $relmodel->getLabels($prop) . ')';
                }


                // ":" - karakter utáni adatok törlése
                $type = explode(':', $type);

                switch ($type[0]) {
                    case 'array':
                    case 'boolean':
                    case 'date':
                    case 'float':
                    case 'integer':

                        if (array_key_exists($find, $_GET)) {
                            $value = $_GET[$find]; //$request->query($find);

                            if (preg_match('/^\[.*\]$/', $value)) {

                                $value = json_decode($value, true);
                                if (in_array(null, $value)) {
                                    $query->where(function ($q) use ($search_property, $value) {
                                        $q->orWhereNull($search_property);
                                        $q->orWhereIn($search_property, $value);
                                    });
                                    static::$searchLog[] = [$search_name, "IN", $value];
                                } else {
                                    $query->whereIn($search_property, $value);
                                    static::$searchLog[] = [$search_name, "IN", $value];
                                }
                                break;
                            }

                            if (preg_match('/,/', $value)) {
                                $query->whereIn($search_property, explode(',', $value));
                                static::$searchLog[] = [$search_name, "IN", $value];
                                break;
                            }
                            $query->where($search_property, "=", $value);
                            static::$searchLog[] = [$search_name, "=", $value];
                        } else {
                            if (array_key_exists($find . '>', $_GET)) {
                                $query->where($search_property, '>=', $_GET[$find . '>']);
                                static::$searchLog[] = [$search_name, ">=", $_GET[$find . '>']];
                            }
                            if (array_key_exists($find . '<', $_GET)) {
                                $query->where($search_property, '<=', $_GET[$find . '<']);
                                static::$searchLog[] = [$search_name, "<=", $_GET[$find . '<']];
                            }
                        }
                        break;
                    case 'string':
                        $value = $_GET[$find]; //$request->query($find);

                        if (preg_match('/^\[.*\]$/', $value)) {
                            $value = json_decode($value, true);
                            if ($relmodel::$latin || in_array($find, $relmodel::$latinSearch)) {
                                $value = collect($value)->map(function ($record) {
                                    return conv($record);
                                })->toArray();
                            }
                            if (!empty($value)) {
                                if (in_array(null, $value)) {
                                    $value[] = '';
                                    $query->where(function ($q) use ($search_property, $value) {
                                        $q->orWhereNull($search_property);
                                        $q->orWhereIn($search_property, $value);
                                    });
                                    static::$searchLog[] = [$search_name, "IN", $value];
                                } else {
                                    $query->whereIn($search_property, $value);
                                    static::$searchLog[] = [$search_name, "IN", $value];
                                }
                                break;
                            }
                        }

                        if ($relmodel::$latin || in_array($find, $relmodel::$latinSearch)) {
                            $value = conv($value);
                        }

                        $value = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
                        if ($this->isPostgre()) {
                            $query->where($search_property, 'ILIKE', '%' . $value . '%');
                            static::$searchLog[] = [$search_name, "ILIKE", $value];
                        } else {
                            $query->where($search_property, 'LIKE', '%' . $value . '%');
                            static::$searchLog[] = [$search_name, "LIKE", $value];
                        }
                        break;
                    case 'datetime':
                        if (array_key_exists($find, $_GET)) {
                            $query->whereDate($search_property, '=', $_GET[$find]);
                            static::$searchLog[] = [$search_name, "=", $_GET[$find]];
                        } else {
                            if (array_key_exists($find . '>', $_GET)) {
                                $query->whereDate($search_property, '>=', $_GET[$find . '>']);
                                static::$searchLog[] = [$search_name, '>=', $_GET[$find . '>']];
                            }
                            if (array_key_exists($find . '<', $_GET)) {
                                $query->whereDate($search_property, '<=', $_GET[$find . '<']);
                                static::$searchLog[] = [$search_name, '<=', $_GET[$find . '<']];
                            }
                        }
                        break;
                    default:
                        if (array_key_exists($find, $_GET)) {
                            $query->where($search_property, '=', $_GET[$find]);
                            static::$searchLog[] = [$search_name, '=', $_GET[$find]];
                        } else {
                            if (array_key_exists($find . '>', $_GET)) {
                                $query->where($search_property, '>=', $_GET[$find . '>']);
                                static::$searchLog[] = [$search_name, '>=', $_GET[$find . '>']];
                            }
                            if (array_key_exists($find . '<', $_GET)) {
                                $query->where($search_property, '<=', $_GET[$find . '<']);
                                static::$searchLog[] = [$search_name, '<=', $_GET[$find . '<']];
                            }
                        }
                        break;
                }
            } else {
                if (array_key_exists($find, $defaultSearch)) {
                    if (is_array($defaultSearch[$find])) {
                        $query->whereIn($search_property, $defaultSearch[$find]);
                    } else {
                        $query->where($search_property, $defaultSearch[$find]);
                    }
                }
            }
        }
        return $query;
    }

    public function scopeSort($query, array $extra_col = [])
    {
        $basemodel = $query->getModel();
        $basetable = $basemodel->getTable();
        $this->relationModels[$basetable] = $basemodel;
        $request = app('request');
        $sort = $request->query('sort>');
        $type = 'asc';
        if (!$sort) {
            $sort = $request->query('sort<');
            $type = 'desc';
        }

        if ($sort) {
            if (array_key_exists($sort, $extra_col)) {
                $query->orderBy($extra_col[$sort], $type);
            } else {
                if (empty($this->selectHelper)) {
                    $plus_sdata = $this->getSearch();
                    if (!empty($plus_sdata)) {
                        $this->selectHelper = [];
                        foreach (array_keys($plus_sdata) as $sprop) {
                            $this->selectHelper[$sprop] = $basetable . '.' . $sprop;
                        }
                    }
                }

                if (array_key_exists($sort, $this->selectHelper)) {
                    $query->orderBy($this->selectHelper[$sort], $type);
                    //másodlagosan keresünk ID-ra, ez azért fontos, mert desc irányba rendezés esetén asc módon fogja rendezni az id oszlopot.
                    if (!empty($this->getKeyName())) $query->orderBy($basetable . '.' . $this->getKeyName(), $type);
                }
            }
        }
    }

    public function getLatinRel()
    {
        $hasLatinRel = false;
        if (count($this->relationModels)) foreach ($this->relationModels as $k => $v) {
            if ($v::$latin) {
                if (!is_array($hasLatinRel)) $hasLatinRel = [];

                $found = preg_grep('/^' . $k . '\./', collect($this->selectHelper)->filter(function ($val) {
                    return is_string($val);
                })->toArray());
                if (!empty($found)) {
                    foreach ($found as $k2 => $v2) {
                        $tmp = explode('.', $v2);
                        $type = $v->getSearch(end($tmp));
                        $tmp = explode('|', $type);
                        if ($tmp[0] == 'string') {
                            $hasLatinRel[] = ($k2);
                        }
                    }
                }
            }
        }
        return $hasLatinRel;
    }

    public function scopeOne($query)
    {
        $record = $query->take(1)->get()->first();
        if ($record) {
            $hasLatinRel = $this->getLatinRel();
            if ($hasLatinRel) {
                foreach ($hasLatinRel as $v) {
                    $record->{$v} =  toUtf($record->{$v});
                }
            }
        }
        // dd($this->relationModels);
        return $record ?: false;
    }

    public function scopeAll($query)
    {
        return $query->get();
    }

    public function getRelCasts()
    {
        $table = $this->getTable();
        $names =
            collect($this->selectHelper)
            ->filter(function ($rec, $k) use ($table) {

                if ($rec instanceof Expression) {
                    if (method_exists(DB::connection(), 'getQueryGrammar')) {
                        $qexpressionValue = $rec->getValue(DB::connection()->getQueryGrammar());
                    } else {
                        $qexpressionValue = call_user_func([$rec, 'getValue']);
                    }
                    return !preg_match('/^' . $table . '/', $qexpressionValue) && !empty($k);
                } else {
                    return !preg_match('/^' . $table . '/', $rec) && !empty($k);
                }
            })
            ->map(function ($rec) {
                if ($rec instanceof Expression) {
                    if (method_exists(DB::connection(), 'getQueryGrammar')) {
                        $rec = $rec->getValue(DB::connection()->getQueryGrammar());
                    } else {
                        $rec = call_user_func([$rec, 'getValue']);
                    }
                }

                $tmp = explode('.', $rec);
                return array_pop($tmp);
            })->toArray();

        return collect($this->selectHelper)
            ->filter(function ($rec, $k) use ($table) {
                if ($rec instanceof Expression) {
                    if (method_exists(DB::connection(), 'getQueryGrammar')) {
                        $rec = $rec->getValue(DB::connection()->getQueryGrammar());
                    } else {
                        $rec = call_user_func([$rec, 'getValue']);
                    }
                }
                return !preg_match('/^' . $table . '/', $rec) && !empty($k);
            })
            ->map(function ($rec) {
                if ($rec instanceof Expression) {
                    if (method_exists(DB::connection(), 'getQueryGrammar')) {
                        $rec = $rec->getValue(DB::connection()->getQueryGrammar());
                    } else {
                        $rec = call_user_func([$rec, 'getValue']);
                    }
                }
                $tmp = explode('.', $rec);
                return array_shift($tmp);
            })
            ->filter(function ($r, $k) use ($names) {
                return array_key_exists($r, $this->relationModels) && (array_key_exists($k, $this->relationModels[$r]->casts) || array_key_exists($names[$k], $this->relationModels[$r]->casts));
            })
            ->map(function ($r, $k) use ($names) {
                if (array_key_exists($names[$k], $this->relationModels[$r]->casts)) return $this->relationModels[$r]->casts[$names[$k]];
                return $this->relationModels[$r]->casts[$k];
            });
    }

    public function scopeCollect($query, $collectionMethodName = "default")
    {
        $collectionMethodName = $collectionMethodName ?: "default";

        //latin konvertáláshoz ellenőrzés
        $hasLatinRel = $this->getLatinRel();

        if (empty($this->attributes)) {
            $tmp = explode('|', $collectionMethodName);
            $relCast = $this->getRelCasts();

            foreach ($tmp as $cname) {

                if (method_exists($query->getModel(), $cname . 'Collection')) {
                    $out = [];
                    foreach ($query->get() as $record) {
                        if ($hasLatinRel) {
                            foreach ($hasLatinRel as $v) {
                                $record->{$v} =  toUtf($record->{$v});
                            }
                        }
                        $relCast->each(function ($r, $k) use ($record) {
                            return $record->casts[$k] = $r;
                        });
                        $newRecData = call_user_func([$record, $cname . 'Collection']);
                        if ($this->showlock) {
                            $newRecData['LockData'] = $record->isLocked();
                        }
                        $out[] = $newRecData;
                    }
                    if (static::$useStatus) {
                        $out = collect($out)->map(function ($rec) {
                            if (array_key_exists('status', $rec)) $rec['status'] = $rec['status'] == 'I' ? 'Aktív' : 'Inaktív';
                            return $rec;
                        });
                    }
                    return $out;
                }
            }
        } else {
            $tmp = explode('|', $collectionMethodName);
            if ($hasLatinRel) {
                foreach ($hasLatinRel as $v) {
                    $this->{$v} =  toUtf($this->{$v});
                }
            }

            if (static::$lastSelectModel && static::class == get_class(static::$lastSelectModel)) {
                $relCast = static::$lastSelectModel->getRelCasts();
                $relCast->each(function ($r, $k) {
                    return $this->casts[$k] = $r;
                });
            }
            foreach ($tmp as $cname) {
                if (method_exists($query->getModel(), $cname . 'Collection')) {
                    $out = call_user_func([$this, $cname . 'Collection']);
                    if (static::$useStatus) {
                        if (array_key_exists('status', $out)) $out['status'] = $out['status'] == 'I' ? 'Aktív' : 'Inaktív';
                    }
                    return $out;
                }
            }
        }
        return  $query->get();
    }

    public function getMeta()
    {

        $out = [];
        foreach ($this->selectHelper as $prop => $dest) {
            if ($dest instanceof Expression) {
                $prop2 = $prop;
                $model = $this;
            } else {
                $tmp = explode('.', $dest);
                $prop2 = array_pop($tmp);
                $rel = implode('.', $tmp);
                if (!array_key_exists($rel, $this->relationModels)) continue;
                $model = $this->relationModels[$rel];
            }
            $is_fillable = in_array($prop, $this->getFillable());
            $type = $model->getSearch($prop2);
            $tmp = explode(":", $type);
            $type = array_shift($tmp);
            if (preg_match('/^date/', $type)) $type = 'date';
            $out[$prop] = [
                'title' => $model->getLabels($prop2) ?: $this->getLabels($prop) ?: $this->getLabels(preg_replace('/name$/', 'id', $prop)),
                'type' => $type,
                'create' => $is_fillable,
                'update' => $is_fillable,
                'validation' => $model->getValidation($prop2),
            ];
            if ($prop == 'id') {
                $out[$prop]['details'] = false;
                $out[$prop]['create'] = false;
                $out[$prop]['update'] = false;
                $out[$prop]['smalldetails'] = false;
                $out[$prop]['search'] = false;
            }
        }

        collect(array_keys($this->getSearch()))->filter(function ($val) {
            return !array_key_exists($val, $this->selectHelper) && !in_array($val, $this->selectHelper);
        })->each(function ($prop) use (&$out) {
            $model = $this;
            $is_fillable = in_array($prop, $this->getFillable());
            $type = $model->getSearch($prop);
            $tmp = explode(":", $type);
            $type = array_shift($tmp);
            if (preg_match('/^date/', $type)) $type = 'date';
            $out[$prop] = [
                'title' => $this->getLabels($prop) ?: $this->getLabels(preg_replace('/name$/', 'id', $prop)),
                'list' => false,
                'search' => false,
                'details' => false,
                'smalldetails' => false,
                'type' => $type,
                'create' => $is_fillable,
                'update' => $is_fillable,
                'validation' => $model->getValidation($prop),
            ];
        });

        if (!empty($this->localJoinNames)) {
            foreach ($this->localJoinNames as $localproop) {
                $localproop = preg_replace('/.*\./', '', $localproop);
                if (array_key_exists($localproop, $out)) {
                    $out[$localproop]['list'] = false;
                    $out[$localproop]['search'] = false;
                    $out[$localproop]['details'] = false;
                    $out[$localproop]['smalldetails'] = false;
                }
            }
        }
        return $out;
    }

    public function scopeGetHeaders($query, $extrtaheaders = [])
    {
        $basemodel = $query->getModel();
        $basetable = $basemodel->getTable();
        $this->relationModels[$basetable] = $basemodel;
        if (empty($this->selectHelper)) {
            $plus_sdata = $this->getSearch();
            if (!empty($plus_sdata)) {
                $this->selectHelper = [];
                foreach (array_keys($plus_sdata) as $sprop) {
                    $this->selectHelper[$sprop] = $basetable . '.' . $sprop;
                }
            }
        }

        $headers = [];
        foreach ($this->selectHelper as $prop => $dest) {
            if (array_key_exists($prop, $extrtaheaders)) {
                $headers[$prop] = $extrtaheaders[$prop];
                unset($extrtaheaders[$prop]);
                continue;
            }
            $tmp = explode('.', $dest);
            $prop2 = array_pop($tmp);
            $rel = implode('.', $tmp);
            if (!array_key_exists($rel, $this->relationModels)) continue;
            $model = $this->relationModels[$rel];
            $headers[$prop] = $model->getLabels($prop2);
        }
        return array_merge($headers, $extrtaheaders);
    }

    public function scopeEntity($query)
    {
        if (!hasJustOneEntity()) {
            $model = $query->getModel();

            $tmp = explode('.', $model::$entity_property);
            $prop2 = array_pop($tmp);
            $rel = implode('.', $tmp);

            if ($rel) { //ha model kapcsolat van
                $query->whereHas($rel, function ($q) use ($prop2) {
                    $q->where($prop2, getEntity());
                });
            } else {
                $query->where($model->getTable() . '.' . $prop2, getEntity());
            }
        }

        return $query;
    }

    public function scopeExport($query, $file_name, $save = false, $collectionMethodName = "export|default")
    {
        $rows = $query->collect($collectionMethodName);
        Excel::xls($query->getHeaders(), $rows, $file_name, $save);
    }

    static function import($file_content, $headers = [], $rowedit = false, $tab_number = 0)
    {
        $classname = get_called_class();
        $model = new $classname;
        $headers = array_merge($model->getLabels(), $headers);
        Excel::import($headers, $file_content, function ($rowdata, $spreadsheetname, $tab_number, $line) use ($classname, $rowedit) {
            $newRecord = new $classname;
            $newRecord->fill($rowdata);
            if ($rowedit) {
                $rowedit($newRecord, $rowdata, $spreadsheetname, $tab_number, $line);
            } else {
                $newRecord->save();
            }
        }, $tab_number);
    }

    public function scopePage($query, $collectionMethodName = "default")
    {
        $request = app('request');

        $uri = $request->getPathInfo();

        if (preg_match('/export$/', $uri)) {
            // return $query->export($collectionMethodName);
            return $query;
        }

        $out = [];

        if ($request->query('getMeta')) {
            $out['meta'] = $this->getMeta();
            $out['useStatus'] = static::$useStatus;
            if (static::$useStatus) $out['defaultSearch'] = ['status' => 'I'];
            $out['reload'] = true;
        } else {
            $total = false;
            $per_page = $request->query('per-page');
            $current_page = $request->query('page') ?: 1;

            if ($per_page) {

                $total = $query->toBase()->getCountForPagination();

                $max = ceil($total / $per_page);
                if ($current_page > $max) $current_page = $max;
                $query->skip(($current_page - 1) * $per_page)->take($per_page);
            }

            if ($total !== false) $out['total'] = $total ?: 0;
            $out['data'] = $query->collect($collectionMethodName);
        }

        return $out;
    }

    public function validate($plusz_data = [], $plus_validation = [], $labels = [], $isUpdate = NULL)
    {
        $plusz_data = array_merge(app('request')->all(), $plusz_data);
        $plus_validation = array_merge($this->getValidation(), $plus_validation);

        if (($this->getKey() && $isUpdate === NULL) || $isUpdate) {
            foreach ($plus_validation as $key => $value) {
                $plus_validation[$key] = str_replace('required', 'filled', $value);
            }
        }

        $labels = array_merge($this->getLabels(), $labels);
        // dd($plusz_data, $plus_validation);

        //ha nincs entitás, akkor feltöltjük
        if (
            preg_match('/required/', $this->getValidation(self::$entity_property)) &&
            (!array_key_exists(self::$entity_property, $plusz_data) || (array_key_exists(self::$entity_property, $plusz_data) && empty($plusz_data[self::$entity_property])))
        ) {
            $plusz_data[self::$entity_property] = getEntity();
        }
        // dd($plusz_data);
        Validator::make($plusz_data, $plus_validation, [], $labels)->validate();
        return $plusz_data;
    }

    static function getHiddenFields()
    {
        $class = static::class;
        return (new $class)->getHidden();
    }

    public function formatArraytoString($array = [], $labelsOnly = false)
    {
        $out = '';
        foreach ($array as $key => $value) {
            if ($key == 'token') $value = '****';
            if ($key == 'password') $value = '****';
            if ($key == 'password2') $value = '****';
            if ($key == 'old_password') $value = '****';
            if ($key == 'old_passwords') $value = '****';
            if ($key == 'current_password') $value = '****';
            if ($key == 'password_confirmation') $value = '****';
            if (in_array($key, $this->hidden)) $value = '****';
            $value = is_array($value) ? var_export($value, true) : $value;
            if ($labelsOnly) {
                $out .= "\t\t" . ($this->getLabels($key) ?  $this->getLabels($key) : $key) . ': ' . $value . "\n";
            } else {
                $out .= "\t\t" . $key . ($this->getLabels($key) ? ' (' . $this->getLabels($key) . ')' : '') . ': ' . $value . "\n";
            }
        }
        return $out;
    }

    public function doLatinRelation()
    {
        if (!$this->latinRelation) {
            static::retrieved(function ($model) {
                foreach ($model->getAttributes() as $key => $value) {
                    if (!array_key_exists($key, $this->selectHelper)) continue;
                    $dest = $this->selectHelper[$key];
                    if (!is_string($dest)) continue;
                    $tmp = explode('.', $dest);
                    $prop2 = array_pop($tmp);
                    $rel = implode('.', $tmp);
                    if (!array_key_exists($rel, $this->relationModels)) continue;
                    $relationModel = $this->relationModels[$rel];
                    if ($relationModel::$latin && is_string($value)) $model->{$key} = toUtf($value);
                }
            });
            $this->latinRelation = true;
        }
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function fileUpload($file, $allowedTypes = false, $folder = null, $UploadCheck = true)
    {
        if ($UploadCheck) UploadCheck::runFromJson($file, $allowedTypes);

        $filepath = (function_exists('getModulAzon') ? call_user_func('getModulAzon') . '/' : '') . preg_replace('/.*\\\\/', '', ($folder ? $folder : static::class)) . '/' . $this->getKey();

        Storage::put($filepath, base64_decode($file["file_content"]));
    }

    static public function getFilePath($id, $full = false, $folder = false)
    {
        $id = $id ?: request()->route('id');
        $path = (function_exists('getModulAzon') ? call_user_func('getModulAzon') . '/' : '') . preg_replace('/.*\\\\/', '', ($folder ? $folder : static::class)) . '/' . $id;
        if (empty($path)) return null;
        if ($full) {
            return preg_replace('/^.*\:/', '', str_replace('\\', '/', Storage::path($path)));
        }
        return $path;
    }

    static function fileDownload($id = null, $folder = null)
    {
        $id = $id ?: request()->route('id');
        $model = static::findOne($id);
        $file = (function_exists('getModulAzon') ? call_user_func('getModulAzon') . '/' : '') . preg_replace('/.*\\\\/', '', ($folder ? $folder : static::class)) . '/' . $id;
        $data = $model->toArray();
        if (array_key_exists('file_name', $data)) return Storage::download($file, $model->file_name);
        if (array_key_exists('filename', $data)) return Storage::download($file, $model->filename);
        if (array_key_exists('fileName', $data)) return Storage::download($file, $model->fileName);
        if (array_key_exists('file', $data)) return Storage::download($file, $model->file);
        if (array_key_exists('name', $data)) return Storage::download($file, $model->name);
        if (array_key_exists('fajlnev', $data)) return Storage::download($file, $model->fajlnev);
        return Storage::download($file);
    }

    static function safeStrip($txt, $prop)
    {
        if (!is_string($txt)) return $txt;
        if (!preg_match('/<.*>/', $txt)) return $txt;

        if (in_array($prop, static::$safeHtml)) $txt = static::sanitizeHtml($txt);
        return htmlentities($txt, ENT_QUOTES, "UTF-8");
    }

    static function sanitizeHtml($input)
    {
        if (empty($input)) return;
        if ($input === false) return false;

        $spaceDelimiter = "#BLANKSPACE#";
        $newLineDelimiter = "#NEWLNE#";

        $returnData = str_replace(" ", $spaceDelimiter, $input);
        $returnData = str_replace("\n", $newLineDelimiter, $returnData);

        //removing <script> tags
        $returnData = preg_replace("/[<][\s]*script[^>]*[>][^<]*[<][\s]*[\/][\s]*script[^>]*[>]/i", "", $returnData);

        $returnData = str_replace($spaceDelimiter, " ", $returnData);
        $returnData = str_replace($newLineDelimiter, "\n", $returnData);

        //removing inline js events
        $returnData = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/", "", $returnData);

        //removing inline js
        $returnData = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i", "", $returnData);

        return $returnData;
    }

    public function safeStripAll()
    {
        $changes = $this->getDirty();
        if (empty($changes)) return;

        foreach ($changes as $key => $value) {
            $this->{$key} = self::safeStrip($value, $key);
        }
    }

    public function fileDelete()
    {
        $id = $this->getKey();
        $dir = preg_replace('/.*\\\\/', '', static::class) . '/' . $id;
        //régi fájl kitörése, ha volt ilyen
        collect(Storage::files($dir))->each(function ($file) {
            Storage::delete($file);
        });
        Storage::deleteDirectory($dir);
    }

    public function beforeCreate()
    {
    }
    public function afterCreate()
    {
    }
    public function beforeUpdate()
    {
        //$changes = $this->getDirty();
        // $original = $this->getOriginal();
    }
    public function afterUpdate()
    {
        // $changes = $this->getChanges();
        // $original = $this->getOriginal();
    }
    public function beforeDelete()
    {
    }
    public function afterDelete()
    {
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if (
                array_key_exists(self::$entity_property, $model->validation) &&
                preg_match('/required/', $model->validation[self::$entity_property]) &&
                empty($model->{self::$entity_property})
            ) {
                $model->{self::$entity_property} = getEntity();
            }
            if ($model::$latin) {
                foreach ($model->getAttributes() as $key => $value) {
                    if (is_string($value)) $model->{$key} = toLatin($value);
                }
            }
            if($model->useStatus){
                if($model->status == 'Aktív') $model->status = 'I';
                if($model->status == 'Inaktív') $model->status = 'N';
            }
            $model->beforeCreate();
            $model->safeStripAll();
        });

        self::created(function ($model) {
            $model->afterCreate();
            if (count(static::$modifyLog) < 21) {
                logger()->info(
                    'Létrehozás:' . get_class($model) .
                        "\n\nAdatok:\n" . $model->formatArraytoString($model->getAttributes())
                );
                static::$modifyLog[] = 'Létrehozás:' . get_class($model) .
                    "\n\tAdatok:\n" . $model->formatArraytoString($model->getAttributes());
            }
        });

        self::updating(function ($model) {
            if ($model::$latin) {
                foreach ($model->getDirty() as $key => $value) {
                    if (is_string($value)) $model->{$key} = toLatin($value);
                }
            }
            $model->beforeUpdate();
            $model->safeStripAll();
        });

        self::updated(function ($model) {
            $model->afterUpdate();
            if (count(static::$modifyLog) < 21) {
                logger()->info(
                    'Módosítás:' . get_class($model) .
                        "\n\nVáltozott adatok:\n" . $model->formatArraytoString($model->getChanges()) .
                        "\nEredeti adatok:\n" . $model->formatArraytoString($model->getOriginal())
                );
                static::$modifyLog[] = 'Módosítás:' . get_class($model) .
                    "\n\tVáltozott adatok:\n" . $model->formatArraytoString($model->getChanges()) .
                    "\n\tEredeti adatok:\n" . $model->formatArraytoString($model->getOriginal());
            }
        });

        self::deleting(function ($model) {
            $model->beforeDelete();
        });

        self::deleted(function ($model) {
            $model->afterDelete();
            if (count(static::$modifyLog) < 21) {
                logger()->info(
                    'Törlés:' . get_class($model) .
                        "\n\nRégi adatok:\n" . $model->formatArraytoString($model->getOriginal())
                );
                static::$modifyLog[] = 'Törlés:' . get_class($model) .
                    "\n\tRégi adatok:\n" . $model->formatArraytoString($model->getOriginal());
            }
        });


        if (static::$latin) {
            static::retrieved(function ($model) {
                foreach ($model->getAttributes() as $key => $value) {
                    if (is_string($value)) $model->{$key} = toUtf($value);
                }
            });
        }
    }
}
