<?php

namespace hws\generator;

use hws\generator\cls\MigrationCls;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrationGenerator
{

    static $base_file_path = 'database/migrations';
    static $modul_azon = null;


    static $exclude_table_prefix = [
        'oauth_',
        'migrations',
        'failed_jobs',
        'password_resets',
        'flyway_schema_history',
    ];
    static $table_prefix = [];

    public function __construct($table = null)
    {
        $output = new BufferedOutput;
        Artisan::call('migrate --force', [], $output);
        echo $output->fetch();

        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        if ($table) self::$table_prefix = [$table];
        (new MigrationCls())->process(self::$modul_azon);
    }
}
