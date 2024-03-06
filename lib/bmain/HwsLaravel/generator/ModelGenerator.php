<?php


namespace hws\generator;


use Illuminate\Support\Facades\DB;
use hws\generator\cls\ModelCls;


class ModelGenerator
{

    static $name_space = 'App\Models';
    static $file_path = 'app/Models';
    static $exclude_table_prefix = [
        'oauth_',
        'migrations',
        'failed_jobs',
        'password_resets',
        'flyway_schema_history',
    ];
    static $table_prefix = [
        // 'oauth'
    ];
    static $modul_azon = null;

    public function __construct(String $table_name = null)
    {
        if($table_name == 'migrations')return;
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        if($table_name){
            (new ModelCls($table_name, self::$modul_azon))->process(true);
        }else{
            $tables = DB::getDoctrineSchemaManager()->listTables();
            foreach($tables as $table){
                (new ModelCls($table->getName(), self::$modul_azon))->process();
            }
        }
    }

}
