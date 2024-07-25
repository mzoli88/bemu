<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE TABLE notify_emails_retries (id INT AUTO_INCREMENT NOT NULL, notify_email_id BIGINT NOT NULL, attempt INT NOT NULL, INDEX notify_email_id (notify_email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = '' ",
    ];

    public $sql_down = [
        "DROP TABLE notify_emails_retries",
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