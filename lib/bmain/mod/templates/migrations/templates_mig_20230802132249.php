<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE TABLE template_messages (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', type VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Típus', notification_text LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Rendszer üzenet', email_subj LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'E-mail tárgy', email_ct LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'E-mail szöveg', push VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Push', push_subj LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Push tárgy', push_text LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Push szöveg', modul_name VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul neve', message_name VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Esemény neve', email VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'E-mail', notification VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Rendszerüzenet', addressee VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Kinek küldi', INDEX entity_id (entity_id), INDEX modul_azon (modul_azon), INDEX type (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Események sablonjai' ",
    ];

    public $sql_down = [
        "ALTER TABLE template_messages DROP FOREIGN KEY template_messages_ibfk_1",
        "DROP TABLE template_messages",
    ];    

    public function up(){
        foreach($this->sql_up as $sq){
            DB::statement($sq);
        }
        if (!config('isBorder')){
            DB::statement("ALTER TABLE template_messages ADD CONSTRAINT template_messages_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
        }

	}

	public function down(){
        foreach($this->sql_down as $sq){
            DB::statement($sq);
        }
    }

};