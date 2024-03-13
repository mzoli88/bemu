<?php
/*
Artisan::command('hws:mig {table?}', function ($table = null) {
    MigrationGenerator::$table_prefix = [];
    new MigrationGenerator($table);
})->describe('Generate model');
*/

namespace hws\generator\cls;

use hws\generator\MigrationGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrationCls
{

    public $input_table;
    public $input_database;

    public function process($modul_azon)
    {

        //teszt adatbázis törlése és létrehozása
        $dbs = DB::getDoctrineSchemaManager()->listDatabases();
        if (in_array('hwsmig', $dbs)) DB::getDoctrineSchemaManager()->dropDatabase("hwsmig");
        DB::getDoctrineSchemaManager()->createDatabase("hwsmig");

        $defaultDb = config('database.default');

        config(['database.connections.hwsmig' => config('database.connections.' . $defaultDb)]);
        config(['database.connections.hwsmig.database' => "hwsmig"]);

        $to_sm = DB::getDoctrineSchemaManager();
        $to_sm->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        config(['database.default' => 'hwsmig']);
        Artisan::call("migrate:refresh --database=hwsmig --force");
        $from_sm = DB::getDoctrineSchemaManager();
        $from_sm->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        config(['database.default' => $defaultDb]);

        $toSchema = $to_sm->createSchema();
        $fromSchema = $from_sm->createSchema();

        foreach ($to_sm->listTables() as $table) {
            $tablename = $table->getName();
            $dropped = false;
            if (!empty(MigrationGenerator::$table_prefix)) {
                foreach (MigrationGenerator::$table_prefix as $prefix) {
                    if (substr($tablename, 0, strlen($prefix)) !== $prefix) {
                        $toSchema->dropTable($tablename);
                        $dropped = true;
                    }
                }
            }
            if ($dropped) continue;
            foreach (MigrationGenerator::$exclude_table_prefix as $exlude) {
                if (substr($tablename, 0, strlen($exlude)) === $exlude) {
                    $toSchema->dropTable($tablename);
                }
            }
        }

        foreach ($from_sm->listTables() as $table) {
            $tablename = $table->getName();
            $dropped = false;
            if (!empty(MigrationGenerator::$table_prefix)) {
                foreach (MigrationGenerator::$table_prefix as $prefix) {
                    if (substr($tablename, 0, strlen($prefix)) !== $prefix) {
                        $fromSchema->dropTable($tablename);
                        $dropped = true;
                    }
                }
            }
            if ($dropped) continue;
            foreach (MigrationGenerator::$exclude_table_prefix as $exlude) {
                if (substr($tablename, 0, strlen($exlude)) === $exlude) {
                    $fromSchema->dropTable($tablename);
                }
            }
        }


        // $comparator = new \Doctrine\DBAL\Schema\Comparator();
        // $schemaDiff = $comparator->compare($fromSchema, $toSchema);
        // dd($schemaDiff->newTables);
        // dd($schemaDiff->changedTables);
        // $sql = $schemaDiff->toSql(DB::getDoctrineConnection()->getDatabasePlatform());

        $sql_up = $fromSchema->getMigrateToSql($toSchema, DB::getDoctrineConnection()->getDatabasePlatform());
        $sql_down = $toSchema->getMigrateToSql($fromSchema, DB::getDoctrineConnection()->getDatabasePlatform());


        if (!empty(MigrationGenerator::$static_tables)) {
            foreach (MigrationGenerator::$static_tables as $stable) {

                // $do_truncate = false;
                // $to_sm->createSequence();

                if (DB::connection('hwsmig')->getSchemaBuilder()->hasTable($stable)) {
                    $tmp_from = DB::connection($defaultDb)->table($stable)->get()->keyBy('id');
                    $tmp_mig = DB::connection('hwsmig')->table($stable)->get()->keyBy('id');

                    if (json_encode($tmp_from) == json_encode($tmp_mig)) continue;

                    $to_delete = [];
                    $to_create = [];
                    $to_update = [];

                    $tmp_from->each(function ($row, $id) use ($tmp_mig, &$to_create, &$sql_up, $stable) {
                        if (!$tmp_mig->has($id)) {
                            $to_create[] = $row;
                        } else {
                            $ch_data = $tmp_mig->get($id);
                            foreach ($row as $key => $value) {
                                if (!array_key_exists($key, (array)$ch_data) || $ch_data->{$key} != $value) {
                                    $sql_up[] = self::update_insert_query($stable, $id, $row);
                                    break;
                                }
                            }
                        }
                    });


                    if (!empty($to_create)) $sql_up[] = self::create_insert_query($stable, $to_create);
                    // dd ($to_create);
                } else {
                    $data = DB::connection($defaultDb)->table($stable)->get()->toArray();
                    if (empty($data)) continue;
                    $sql_up[] = self::create_insert_query($stable, DB::connection($defaultDb)->table($stable)->get()->toArray());
                }
            }
        }


        // dd($sql_up);
        // print_r($sql_down);
        // dd(addslashes('What does "yolo" mean?'));

        $sql_up = collect($sql_up)->map(function ($r) {
            return str_replace('"', '\\"', $r);
        })->toArray();
        $sql_down = collect($sql_down)->map(function ($r) {
            return str_replace('"', '\\"', $r);
        })->toArray();

        if (count($sql_up)) {
            $this->make($sql_up, $sql_down, $modul_azon);
        } else {
            echo "No changes found!\n";
        }
        return $this;
    }



    static function create_insert_query($tablename, $array)
    {
        $keys = array_keys((array)$array[0]);
        return "INSERT INTO $tablename (" . implode(', ', $keys) . ") "
            . "VALUES " . collect($array)->map(function ($x) {
                return "('" . implode("', '", array_values((array)$x)) . "')";
            })->implode(', ');
    }

    static function update_insert_query($tablename, $id, $array)
    {
        $values = collect($array)->filter(function ($v, $k) {
            return $k != 'id';
        })->map(function ($v, $k) {
            return "$k = '$v'";
        })->implode(', ');
        return "UPDATE $tablename SET $values WHERE id = $id";
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

    public function make($sql_up, $sql_down, $modul_azon)
    {

        $laravel_version = explode('.', app()->version());


        if ($modul_azon) {
            $cname = date('YmdHis');
            config()->set('modul_azon', $modul_azon);
            $fname = $modul_azon . '_mig_' . $cname;
        } else {
            $cname = time();
            $fname = date('Y_m_d_His_') . config('modul_azon', 'mig') . '_mig' . $cname;
        }

        switch ($laravel_version[0]) {
            case '6':
                $template = realpath(__DIR__ . "/../temp/migration.blade.php");
                break;
            default:
                $template = realpath(__DIR__ . "/../temp/migration2.blade.php");
                break;
        }


        if ($template) {
            if ($modul_azon) {
                $directory = $this->fkdir(MigrationGenerator::$base_file_path . "/");
                config()->set('modul_azon', $modul_azon);
            } else {
                $directory = $this->fkdir(app()->basePath() . "/" . MigrationGenerator::$base_file_path . "/");
            }


            // $files = glob($directory . "*");
            // $cname = preg_replace(['/.*\_mig/', '/\.php$/'], '', collect($files)->max());


            // dd($directory, $cname, $files);

            $file_content = view()->file($template)->with([
                "php_start" => "?php",
                "sql_up" => $sql_up, true,
                "sql_down" => $sql_down,
                "cname" => config('modul_azon', 'mig') . 'Mig' . $cname
            ])->render();


            file_put_contents($directory . $fname . '.php', $file_content);
            DB::table('migrations')->insert([
                'migration' => $fname,
                'batch' => DB::table('migrations')->max('batch') + 1 ?: 1
            ]);
            echo "Created:\n" . $directory . $fname . "\n";
        }
    }
}
