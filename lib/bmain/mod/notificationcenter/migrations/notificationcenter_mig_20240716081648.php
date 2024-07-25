<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "ALTER TABLE notify_pushs ADD can_be_deleted VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' COLLATE `utf8mb3_hungarian_ci` COMMENT 'Törölhető'",
    ];

    public $sql_down = [
        "ALTER TABLE notify_pushs DROP can_be_deleted",
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