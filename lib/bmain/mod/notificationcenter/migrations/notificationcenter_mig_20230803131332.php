<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        
        "INSERT ignore INTO `notify_priorities` (`id`, `priority`, `label`) VALUES
        (1,	'Low',	'Alacsony'),
        (2,	'High',	'Magas');",

        "INSERT ignore INTO `notify_states` (`id`, `notify_state`) VALUES
        (1,	'Új'),
        (2,	'Hibás küldés'),
        (3,	'Sikeres küldés');"
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