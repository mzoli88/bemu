<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [

        "update  `notify_states` set `notify_state` = 'Sikertelen továbbítás' WHERE  `id` = 2;",
         "update  `notify_states` set `notify_state` = 'Sikeres továbbítás' WHERE  `id` = 3;"
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