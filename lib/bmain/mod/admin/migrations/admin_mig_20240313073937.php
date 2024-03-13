<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "ALTER TABLE `nevek_csoport` ADD `menu_azon` varchar(30) COLLATE 'utf8_hungarian_ci' NULL",
        "ALTER TABLE `b_menu` ADD `modul_azon` varchar(255) COLLATE 'utf8_hungarian_ci' NULL, ADD `menu_azon` varchar(255) COLLATE 'utf8_hungarian_ci' NULL AFTER `modul_azon`",
        "ALTER TABLE `nevek_csoport` ADD INDEX `modul_azon_menu_azon` (`modul_azon`, `menu_azon`)",
    ];

    public $sql_down = [
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