<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "ALTER TABLE notify_emails ADD cc_emails TEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Másolat', ADD bcc_emails TEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Titkos másolat'",
    ];

    public $sql_down = [
        "ALTER TABLE notify_emails DROP cc_emails, DROP bcc_emails",
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