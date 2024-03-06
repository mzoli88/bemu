<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE TABLE admin_params (id INT UNSIGNED AUTO_INCREMENT NOT NULL, modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', `key` VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Kulcs', value LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Érték', INDEX `key` (`key`), INDEX modul_azon (modul_azon), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Paraméterek' ",
    ];

    public $sql_down = [
        "DROP TABLE admin_params",
    ];    

    public function up(){
        foreach($this->sql_up as $sq){
            DB::statement($sq);
        }
	}

	public function down(){
        foreach($this->sql_down as $sq){
            DB::statement($sq);
        }
    }

};