<?php


namespace hws\generator\cls;

use hws\generator\ModelGenerator;
use Illuminate\Support\Facades\DB;

class ModelCls
{

    protected $schema;

    protected $table;
    protected $primaryKey = 'id';
    protected $timestamps = true;
    protected $labels = [];
    protected $fillable = [];

    // public $dateFormat = 'U';
    protected $casts = [];
    protected $relations = [];
    protected $validation = [];

    static $stypes = [
        'array'        => 'array',
        'simple_array' => 'array',
        'json_array'   => 'string',
        'bigint'       => 'integer',
        'boolean'      => 'integer',
        'datetime'     => 'datetime:Y.m.d. H:i:s',
        'datetimetz'   => 'datetime:Y.m.d. H:i:s',
        'date'         => 'date:Y.m.d.',
        'time'         => 'datetime:H:i:s',
        'decimal'      => 'double',
        'integer'      => 'integer',
        'object'       => 'object',
        'smallint'     => 'integer',
        'string'       => 'string',
        'text'         => 'string',
        'binary'       => 'string',
        'blob'         => 'string',
        'float'        => 'float',
        'guid'         => 'string',
    ];
    static $modul_azon = null;

    public function __construct(String $table_name, $modul_azon)
    {
        $this->schema = DB::getDoctrineSchemaManager()->listTableDetails($table_name);
        if ($modul_azon) {
            self::$modul_azon = $modul_azon;
        }
    }

    public function process($force = false)
    {
        $tablename = $this->schema->getName();

        //exlude ellenőrzése
        foreach (ModelGenerator::$exclude_table_prefix as $exlude) {
            if (substr($tablename, 0, strlen($exlude)) === $exlude) return false;
        }

        //prefix ellenőrzése
        $match = false;
        if (empty(ModelGenerator::$table_prefix)) {
            $match = true;
        } else {
            foreach (ModelGenerator::$table_prefix as $prefix) {
                if (substr($tablename, 0, strlen($prefix)) === $prefix) $match = true;
            }
        }
        if (!$match && !$force) return false;


        $this->getTable();
        $this->getPrimaryKey();
        $this->getTimestamps();
        $this->getLabels();
        $this->getFillable();
        $this->getCasts();
        $this->getRelations();
        $this->getValidation();
        $this->make();
        return $this;
    }

    public function getModelName(String $table_name)
    {
        $tmp = explode(".", $table_name);
        $table_name = end($tmp);
        foreach (ModelGenerator::$table_prefix as $prefix) {
            $table_name = preg_replace('/^' . $prefix . '/', '', $table_name);
        }
        $tmp = explode('_', str_replace('-', '_', $table_name));

        foreach ($tmp as $key => $value) {
            $tmp[$key] = ucfirst($value);
        }
        return implode('', $tmp);
    }

    public function getTable()
    {
        $this->table = $this->schema->getName();
    }

    public function getPrimaryKey()
    {
        $primaryKey = $this->schema->getPrimaryKey();
        if (!$primaryKey) return;
        $primaryKey = $primaryKey->getColumns();
        if (!is_array($primaryKey)) return;
        $this->primaryKey = reset($primaryKey);
    }

    public function getTimestamps()
    {
        $cols = $this->schema->getColumns();
        $this->timestamps = array_key_exists('created_at', $cols) && array_key_exists('updated_at', $cols);
    }

    public function getLabels()
    {
        $cols = $this->schema->getColumns();
        foreach ($cols as $name => $col) {
            $name = $col->getName(); //Nagy betűk megmaradjanak
            $comment = $col->getComment();
            if (!empty($comment)) {
                $this->labels[$name] = $comment;
            } else {
                if ($name == $this->primaryKey) {
                    $this->labels[$name] = "Azonosító";
                    continue;
                }
                if ($name == 'created_at') {
                    $this->labels[$name] = "Létrehozás dátum";
                    continue;
                }
                if ($name == 'updated_at') {
                    $this->labels[$name] = "Módosítás dátum";
                    continue;
                }
            }
        }
    }

    public function getFillable()
    {
        $cols = $this->schema->getColumns();
        foreach ($cols as $name => $col) {
            $name = $col->getName(); //Nagy betűk megmaradjanak
            if ($name == $this->primaryKey) {
                if ($col->getAutoincrement()) continue;
            };
            if ($name == "created_at") continue;
            if ($name == "updated_at") continue;
            $this->fillable[] = $name;
        }
    }

    public function getCasts()
    {
        $cols = $this->schema->getColumns();
        foreach ($cols as $name => $col) {
            $name = $col->getName(); //Nagy betűk megmaradjanak
            $type = $col->getType()->getName();
            if (array_key_exists($type, self::$stypes)) {
                $this->casts[$name] = self::$stypes[$type];
            } else {
                $this->casts[$name] = NULL;
            }
        }
    }

    public function getValidation()
    {
        $cols = $this->schema->getColumns();
        foreach ($cols as $name => $col) {
            $name = $col->getName(); //Nagy betűk megmaradjanak
            if ($name == $this->primaryKey) continue;
            if ($name == "created_at") continue;
            if ($name == "updated_at") continue;
            $type = $col->getType()->getName();
            $rules = [];
            if ($col->getNotnull() && $col->getDefault() === null) {
                $rules[] = 'required';
            } else {
                $rules[] = 'nullable';
            }

            switch ($type) {
                case 'string':
                    if ($col->getLength()) {
                        $rules[] = 'max:' . $col->getLength();
                    }
                    break;
                case 'bigint':
                    $rules[] = 'integer';
                    $rules[] = 'digits_between:1,20';
                    break;
                case 'integer':
                    $rules[] = 'integer';
                    $rules[] = 'digits_between:1,11';
                    break;
                case 'smallint':
                    $rules[] = 'integer';
                    $rules[] = 'digits_between:1,10';
                    break;
                case 'date':
                case 'datetime':
                case 'datetimetz':
                    $rules[] = 'date';
                    break;
                default:
                    break;
            }
            if (!empty($rules)) $this->validation[$name] = implode('|', $rules);
        }
    }

    public function getRelations()
    {

        $foreignKeys = DB::getDoctrineSchemaManager()->listTableForeignKeys($this->schema->getName());
        foreach ($foreignKeys as $tableForeignKey) {
            $tableForeignColumns = $tableForeignKey->getForeignColumns();
            if (count($tableForeignColumns) !== 1) {
                continue;
            }

            $name =  $this->getModelName($tableForeignKey->getForeignTableName());

            $this->relations[] = [
                "rel" => "belongsTo",
                "foreign_key" => $tableForeignKey->getLocalColumns()[0],
                "local_key" => $tableForeignColumns[0],
                "name" => 'Rel_' . preg_replace(['/\_id$/', '/\_ID$/', '/id$/', '/Id$/', '/ID$/'], '', $tableForeignKey->getLocalColumns()[0]),
                "class" => $name,
            ];
        }

        $tables = DB::getDoctrineSchemaManager()->listTables();
        foreach ($tables as $table) {
            if ($table->getName() === $this->schema->getName()) continue;
            // dd($this->schema->getForeignKeys());
            $foreignKeys = $table->getForeignKeys();
            foreach ($foreignKeys as $name => $foreignKey) {
                // dd($foreignKey->getForeignTableName(),$this->schema->getName());
                if ($foreignKey->getForeignTableName() !== $this->schema->getName()) continue;

                $localColumns = $foreignKey->getLocalColumns();
                // összetett idegen kulcs esetén ne csináljon semmit
                if (count($localColumns) !== 1) {
                    continue;
                }

                $tableName = $foreignKey->getLocalTableName();
                $foreignColumn = $localColumns[0];
                $localColumn = $foreignKey->getForeignColumns()[0];
                // dd($tableName,$foreignColumn,$localColumn,$this->schema->getName());
                if ($this->isColumnUnique($table, $foreignColumn)) {
                    $rel = "hasOne";
                } else {
                    $rel = "hasMany";
                }

                $this->relations[] = [
                    "rel" => $rel,
                    "foreign_key" => $foreignColumn,
                    "local_key" => $localColumn,
                    "name" => 'Rel_' . $this->getModelName($tableName) . '_' . preg_replace(['/\_id$/', '/\_ID$/', '/id$/', '/Id$/', '/ID$/'], '', $foreignColumn),
                    "class" => $this->getModelName($tableName),
                ];
            }
        }
    }

    protected function isColumnUnique($table, $column)
    {
        foreach ($table->getIndexes() as $index) {
            $indexColumns = $index->getColumns();
            if (count($indexColumns) !== 1) {
                continue;
            }
            $indexColumn = $indexColumns[0];
            if ($indexColumn === $column && $index->isUnique()) {
                return true;
            }
        }

        return false;
    }

    public function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function replace_between($str, $needle_start, $needle_end, $replacement, &$update_file)
    {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);

        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;

        $tmp = substr_replace($str, '', 0, $start);
        $tmp = substr_replace($tmp, '', $end - $start);

        if(trim(preg_replace('/\s\s+/', ' ', $tmp)) == trim(preg_replace('/\s\s+/', ' ', $replacement))){
            return $str;
        }

        $update_file = true;

        return substr_replace($str, $replacement, $start, $end - $start);
    }

    public function fkdir($path)
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $tmp_exp = explode(DIRECTORY_SEPARATOR, $path);
        array_pop($tmp_exp);
        $tmp_string = array_shift($tmp_exp);
        if (!empty($tmp_exp)) {
            foreach ($tmp_exp as $next) {
                $tmp_string .= DIRECTORY_SEPARATOR . $next;
                if (!is_dir($tmp_string)) mkdir($tmp_string);
            }
        }
        return $path;
    }

    public function make()
    {
        if (self::$modul_azon) {
            $file = $this->fkdir(ModelGenerator::$file_path . "/" . $this->getModelName($this->table) . '.php');
        } else {
            $file = $this->fkdir(app()->basePath() . "/" . ModelGenerator::$file_path . "/" . $this->getModelName($this->table) . '.php');
        }
        if (file_exists($file)) {
            $ct = file_get_contents($file);
            $update_file = false;

            //label módosítás
            $labels = view()->file(realpath(__DIR__ . "/../temp/labels.blade.php"))
                ->with([
                    "labels" => $this->labels,
                ])->render();
            $ct = $this->replace_between($ct, 'protected $labels = [', '];', "\n\t\t" . $labels, $update_file);

            // dd($ct);

            //validáció módosítás
            $validation = view()->file(realpath(__DIR__ . "/../temp/validation.blade.php"))
                ->with([
                    "validation" => $this->validation,
                ])->render();
            $ct = $this->replace_between($ct, 'protected $validation = [', '];', "\n\t\t" . $validation, $update_file);

            //casts
            $casts = view()->file(realpath(__DIR__ . "/../temp/casts.blade.php"))
                ->with([
                    "casts" => $this->casts,
                ])->render();
            $ct = $this->replace_between($ct, 'protected $casts = [', '];', "\n\t\t" . $casts, $update_file);

            //fillable
            $old = $this->get_string_between($ct, 'protected $fillable = [', '];');
            $commented = collect(explode("\n", $old))->filter(function ($row) {
                return preg_match('/^\/\//', trim($row));
            })->map(function ($row) {
                return $this->get_string_between($row, '"', '"');
            })->toArray();
            $fillable = view()->file(realpath(__DIR__ . "/../temp/fillable.blade.php"))
                ->with([
                    "fillable" => $this->fillable,
                    "commented" => $commented,
                ])->render();
            $ct = $this->replace_between($ct, 'protected $fillable = [', '];', "\n\t\t" . $fillable, $update_file);


            if (!empty($this->relations)) {
                foreach ($this->relations as $key => $val) {
                    if (strpos($ct, 'public function ' . $val['name']) === false) {
                        $relation = view()->file(realpath(__DIR__ . "/../temp/relation.blade.php"))
                            ->with([
                                "key" => $key,
                                "value" => $val,
                            ])->render();
                        // dd($relation);
                        // $ct = $this->replace_between($ct, 'protected $fillable = [', '];', "\n\t\t" . $fillable);
                        $ct = preg_replace('/\}\s+$/', "\n\t" . $relation . "\n}\n", $ct);
                        $update_file = true;
                    }
                }
            }

            if ($update_file) {
                file_put_contents($file, $ct);
                echo " - updated - " . $this->getModelName($this->table) . "\n";
            }
        } else {

            $template = realpath(__DIR__ . "/../temp/model.blade.php");
            if ($template) {
                $file_content = view()->file($template)->with([
                    "php_start" => "?php",
                    "name_space" => ModelGenerator::$name_space,
                    "model_name" => $this->getModelName($this->table),
                    "table" => $this->table,
                    "primaryKey" => $this->primaryKey == 'id' ? false : $this->primaryKey,
                    "timestamps" => !$this->timestamps,
                    "labels" => $this->labels,
                    "fillable" => $this->fillable,
                    "casts" => $this->casts,
                    "validation" => $this->validation,
                    "relations" => $this->relations,
                    "useStatus" => in_array('status', $this->fillable),
                ])->render();
                file_put_contents($file, $file_content . "\n");
                echo " - created - " . $this->getModelName($this->table) . "\n";
            }
        }
    }
}
