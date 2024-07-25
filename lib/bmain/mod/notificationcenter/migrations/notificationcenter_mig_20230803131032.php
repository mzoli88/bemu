<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE TABLE notify_daily_emails (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', priority INT NOT NULL COMMENT 'Prioritás', day DATE NOT NULL COMMENT 'Nap', to_out INT DEFAULT 0 NOT NULL COMMENT 'Kiküldendő', successful INT DEFAULT 0 NOT NULL COMMENT 'Sikeres', failed INT DEFAULT 0 NOT NULL COMMENT 'Sikertelen', resend INT DEFAULT 0 NOT NULL COMMENT 'Úraküldés', entity_id INT NOT NULL COMMENT 'Entitás', INDEX priority_day_entity_id (priority, day, entity_id), INDEX IDX_7BA665D962A6DC27 (priority), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Napi e-mail statisztika' ",
        "CREATE TABLE notify_daily_pushs (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', priority INT NOT NULL COMMENT 'Prioritás', day DATE NOT NULL COMMENT 'Nap', to_out INT DEFAULT 0 NOT NULL COMMENT 'Kiküldendő', successful INT DEFAULT 0 NOT NULL COMMENT 'Sikeres', failed INT DEFAULT 0 NOT NULL COMMENT 'Sikertelen', resend INT DEFAULT 0 NOT NULL COMMENT 'Úraküldés', entity_id INT DEFAULT NULL COMMENT 'Entitás', INDEX day_priority_entity_id (day, priority, entity_id), INDEX priority (priority), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Napi push üzenet statisztika' ",
        "CREATE TABLE notify_email_attachments (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', notify_email_id INT NOT NULL COMMENT 'Email azonosító', filename VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Csatolás neve', filep_path VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Csatolás elérése', INDEX notify_email_id (notify_email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'E-mail csatolások' ",
        "CREATE TABLE notify_emails (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', priority INT NOT NULL COMMENT 'Prioritás', state INT NOT NULL COMMENT 'Állapot', user_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'Létrehozó felhasználó', subject VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Levél tárgya', body LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Levél tartalma', sender_name VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Levél feladó neve', sender_email_address VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Levél feladó e-mail címe', is_html VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'I' NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Html tartalom', sender_params LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'E-mail továbbítás paraméterei', addressee VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Címzett e-mail címe', sent_error LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Továbbítási hiba', modul_id VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', created_at DATETIME DEFAULT NULL COMMENT 'Létrehozás ideje', updated_at DATETIME DEFAULT NULL COMMENT 'Továbbítás ideje', sent_date DATETIME DEFAULT NULL COMMENT 'Továbbítás ideje', modul_record_id INT DEFAULT NULL COMMENT 'Küldő modul azonosító', callback VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Eredmény visszaadás', INDEX created_at (created_at), INDEX entity_id (entity_id), INDEX modul_id (modul_id), INDEX priority (priority), INDEX sent_date (sent_date), INDEX state (state), INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'E-mail üzenetek' ",
        "CREATE TABLE notify_notifications (id BIGINT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', user_id BIGINT UNSIGNED NOT NULL COMMENT 'Felhasználó azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', date DATETIME DEFAULT NULL COMMENT 'Dátum', modul_azon VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', type VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Esemény', `read` VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Olvasott', message LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Tartalom', message_search LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Keresés segéd', INDEX entity_id (entity_id), INDEX modul_azon (modul_azon), INDEX `read` (`read`), INDEX type (type), INDEX user_id (user_id), INDEX user_id_read (user_id, `read`), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Rendszerüzentek' ",
        "CREATE TABLE notify_priorities (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', priority VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Proiritás', label VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Cimke', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Üzenet prioritások' ",
        "CREATE TABLE notify_pushs (id BIGINT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', priority INT NOT NULL COMMENT 'Prioritás', state INT NOT NULL COMMENT 'Állapot', title VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Címe', body LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Tartalma', badge VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Csomag', icon VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Ikon', click_action VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Kattintás akció', sound VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT 'default' COLLATE `utf8mb3_hungarian_ci` COMMENT 'zene', tag VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'tag', sender_params LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Push továbbítás paraméterei', sent_error LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Továbbítás hiba', modul_id VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', token LONGTEXT CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Címzett token', fmc_result_json LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'FCM válasza', modul_record_id INT DEFAULT NULL COMMENT 'Küldő modul azonosító', created_at DATETIME DEFAULT NULL COMMENT 'Létrehozás ideje', updated_at DATETIME DEFAULT NULL, sent_date DATETIME DEFAULT NULL COMMENT 'Továbbítás ideje', callback VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Válasz visszaadása', INDEX created_at (created_at), INDEX entity_id (entity_id), INDEX modul_id (modul_id), INDEX modul_record_id (modul_record_id), INDEX priority (priority), INDEX sent_date (sent_date), INDEX state (state), INDEX token (token(170)), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Push üzenetek' ",
        "CREATE TABLE notify_queues_count (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', priority INT NOT NULL COMMENT 'Prioritás', type VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Típus', count INT DEFAULT 0 NOT NULL COMMENT 'Darabszám', INDEX entity_id_type_priority (entity_id, type, priority), INDEX priority (priority), INDEX IDX_8B046BF881257D5D (entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Üzenet várakozási sor számláló' ",
        "CREATE TABLE notify_states (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', notify_state VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Állapotok', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Üzenet állapotok' ",
        "ALTER TABLE notify_daily_emails ADD CONSTRAINT notify_daily_emails_ibfk_1 FOREIGN KEY (priority) REFERENCES notify_priorities (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_daily_pushs ADD CONSTRAINT notify_daily_pushs_ibfk_1 FOREIGN KEY (priority) REFERENCES notify_priorities (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_email_attachments ADD CONSTRAINT notify_email_attachments_ibfk_1 FOREIGN KEY (notify_email_id) REFERENCES notify_emails (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_emails ADD CONSTRAINT notify_emails_ibfk_2 FOREIGN KEY (priority) REFERENCES notify_priorities (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_emails ADD CONSTRAINT notify_emails_ibfk_3 FOREIGN KEY (state) REFERENCES notify_states (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_pushs ADD CONSTRAINT notify_pushs_ibfk_2 FOREIGN KEY (priority) REFERENCES notify_priorities (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_pushs ADD CONSTRAINT notify_pushs_ibfk_3 FOREIGN KEY (state) REFERENCES notify_states (id) ON UPDATE NO ACTION",
        "ALTER TABLE notify_queues_count ADD CONSTRAINT notify_queues_count_ibfk_2 FOREIGN KEY (priority) REFERENCES notify_priorities (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
    ];

    public $sql_down = [
        "ALTER TABLE notify_daily_emails DROP FOREIGN KEY notify_daily_emails_ibfk_1",
        "ALTER TABLE notify_daily_pushs DROP FOREIGN KEY notify_daily_pushs_ibfk_1",
        "ALTER TABLE notify_email_attachments DROP FOREIGN KEY notify_email_attachments_ibfk_1",
        "ALTER TABLE notify_emails DROP FOREIGN KEY notify_emails_ibfk_1",
        "ALTER TABLE notify_emails DROP FOREIGN KEY notify_emails_ibfk_2",
        "ALTER TABLE notify_emails DROP FOREIGN KEY notify_emails_ibfk_3",
        "ALTER TABLE notify_emails DROP FOREIGN KEY notify_emails_ibfk_4",
        "ALTER TABLE notify_notifications DROP FOREIGN KEY notify_notifications_ibfk_3",
        "ALTER TABLE notify_notifications DROP FOREIGN KEY notify_notifications_ibfk_4",
        "ALTER TABLE notify_pushs DROP FOREIGN KEY notify_pushs_ibfk_1",
        "ALTER TABLE notify_pushs DROP FOREIGN KEY notify_pushs_ibfk_2",
        "ALTER TABLE notify_pushs DROP FOREIGN KEY notify_pushs_ibfk_3",
        "ALTER TABLE notify_queues_count DROP FOREIGN KEY notify_queues_count_ibfk_1",
        "ALTER TABLE notify_queues_count DROP FOREIGN KEY notify_queues_count_ibfk_2",
        "DROP TABLE notify_daily_emails",
        "DROP TABLE notify_daily_pushs",
        "DROP TABLE notify_email_attachments",
        "DROP TABLE notify_emails",
        "DROP TABLE notify_notifications",
        "DROP TABLE notify_priorities",
        "DROP TABLE notify_pushs",
        "DROP TABLE notify_queues_count",
        "DROP TABLE notify_states",
    ];    

    public function up(){
        foreach($this->sql_up as $sq){
            DB::statement($sq);
        }
        if (!config('isBorder')){
            DB::statement("ALTER TABLE notify_queues_count ADD CONSTRAINT notify_queues_count_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
            DB::statement("ALTER TABLE notify_pushs ADD CONSTRAINT notify_pushs_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION");
            DB::statement("ALTER TABLE notify_notifications ADD CONSTRAINT notify_notifications_ibfk_4 FOREIGN KEY (entity_id) REFERENCES admin_entities (id)");
            DB::statement("ALTER TABLE notify_notifications ADD CONSTRAINT notify_notifications_ibfk_3 FOREIGN KEY (user_id) REFERENCES admin_users (id)");
            DB::statement("ALTER TABLE notify_emails ADD CONSTRAINT notify_emails_ibfk_4 FOREIGN KEY (user_id) REFERENCES admin_users (id) ON UPDATE NO ACTION ON DELETE SET NULL");
            DB::statement("ALTER TABLE notify_emails ADD CONSTRAINT notify_emails_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION");
        }
	}

	public function down(){
        foreach($this->sql_down as $sq){
            DB::statement($sq);
        }
    }

};