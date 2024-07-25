<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE INDEX addressee ON notify_emails (addressee)",
        "ALTER TABLE notify_emails_retries ADD in_queue VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' NOT NULL COLLATE `utf8mb3_hungarian_ci`",
        "CREATE INDEX in_queue ON notify_emails_retries (in_queue)",
    ];

    public $sql_down = [
        "DROP INDEX addressee ON notify_emails",
        "DROP INDEX in_queue ON notify_emails_retries",
        "ALTER TABLE notify_emails_retries DROP in_queue",
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