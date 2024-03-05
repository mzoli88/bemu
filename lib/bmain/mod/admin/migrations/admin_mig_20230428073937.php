<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE TABLE admin_documents (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Megnevezés', modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul', file_name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Fájl név', INDEX modul_azon (modul_azon), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Dokumentumok' ",
        "CREATE TABLE admin_entities (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Entitás', status VARCHAR(1) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Státusz', INDEX status (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Entitások' ",
        "CREATE TABLE admin_group_perms (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', group_id INT NOT NULL COMMENT 'Szerepkör azonosító', modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', perm VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Jogosultság', INDEX group_id (group_id), INDEX entity_id_group_id (entity_id, group_id), INDEX IDX_B014FBDF81257D5D (entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Szerepkör jögosultságok' ",
        "CREATE TABLE admin_groups (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Szerepkör', status VARCHAR(1) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Státusz', INDEX name (name(100)), INDEX status (status), INDEX entity_id (entity_id), INDEX id_status (id, status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Szerepkörök' ",
        "CREATE TABLE admin_params (id INT UNSIGNED AUTO_INCREMENT NOT NULL, modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', `key` VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Kulcs', value LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Érték', INDEX `key` (`key`), INDEX modul_azon (modul_azon), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Paraméterek' ",
        "CREATE TABLE admin_user_entities (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', user_id BIGINT UNSIGNED NOT NULL COMMENT 'Felhasználó azonosító', INDEX entity_id (entity_id), INDEX user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Felhasználó entitások' ",
        "CREATE TABLE admin_user_groups (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', user_id BIGINT UNSIGNED NOT NULL COMMENT 'Felhasználó azonosító', group_id INT NOT NULL COMMENT 'Szerepkör azonosító', INDEX user_id (user_id), INDEX group_id (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Fehasználó szerepkörk' ",
        "CREATE TABLE admin_user_perms (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', user_id BIGINT UNSIGNED NOT NULL COMMENT 'Felhasználó azonosító', modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', perm VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Jogosultság', INDEX modul_azon (modul_azon), INDEX user_id (user_id), INDEX perm (perm), INDEX entity_id_user_id (entity_id, user_id), INDEX IDX_57DEC46C81257D5D (entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Felhasználó jogok' ",
        "CREATE TABLE admin_user_states (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', state VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Státusz', priority INT NOT NULL COMMENT 'Sorrend', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Felhasználó státuszok' ",
        "CREATE TABLE admin_users (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', state_id INT DEFAULT 2 NOT NULL COMMENT 'Státusz', name VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Név', email VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'E-mail', email_verified_at DATETIME DEFAULT NULL COMMENT 'E-mail aktiválás ideje', password VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Jelszó', remember_token VARCHAR(100) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Elfelejtett jelszó token', sys_admin VARCHAR(1) CHARACTER SET utf8mb3 DEFAULT 'N' NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Supervisor', api_token VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Belépés token', created_at DATETIME DEFAULT NULL COMMENT 'Létrehozás ideje', updated_at DATETIME DEFAULT NULL COMMENT 'Módosítás ideje', last_login DATETIME DEFAULT NULL COMMENT 'Utolsó belépés', inactive_date DATETIME DEFAULT NULL COMMENT 'Inaktiválás / felfüggesztés időpontja', last_password_modify DATETIME DEFAULT NULL COMMENT 'Utolsó jelszó módosítás ideje', cookie_token VARCHAR(100) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Süti token', login VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Felhasználónév', login_try INT DEFAULT 0 NOT NULL COMMENT 'Bejelentkezési kísérletek száma', old_passwords LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Régi jelszavak', uuid VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'UUID', activation_token VARCHAR(255) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Token', created_by BIGINT UNSIGNED DEFAULT NULL COMMENT 'Létrehozó felhasználó', inactivation_justification LONGTEXT CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Inaktiválás indoklása', UNIQUE INDEX login (login), INDEX email (email), INDEX last_login (last_login), INDEX api_token (api_token), INDEX remember_token (remember_token), INDEX state (state_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Felhasználók' ",
        "ALTER TABLE admin_group_perms ADD CONSTRAINT admin_group_perms_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_group_perms ADD CONSTRAINT admin_group_perms_ibfk_2 FOREIGN KEY (group_id) REFERENCES admin_groups (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_groups ADD CONSTRAINT admin_groups_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_user_entities ADD CONSTRAINT admin_user_entities_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_user_entities ADD CONSTRAINT admin_user_entities_ibfk_2 FOREIGN KEY (user_id) REFERENCES admin_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_user_groups ADD CONSTRAINT admin_user_groups_ibfk_1 FOREIGN KEY (user_id) REFERENCES admin_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_user_groups ADD CONSTRAINT admin_user_groups_ibfk_2 FOREIGN KEY (group_id) REFERENCES admin_groups (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_user_perms ADD CONSTRAINT admin_user_perms_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_user_perms ADD CONSTRAINT admin_user_perms_ibfk_2 FOREIGN KEY (user_id) REFERENCES admin_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "ALTER TABLE admin_users ADD CONSTRAINT admin_users_ibfk_1 FOREIGN KEY (state_id) REFERENCES admin_user_states (id) ON UPDATE NO ACTION ON DELETE NO ACTION",
        "INSERT INTO `admin_user_states` (`id`, `state`, `priority`) VALUES
        (1,	'Aktiválásra váró',1),
        (2,	'Aktív',2),
        (3,	'Inaktivált',6),
        (4,	'Törölt',7),
        (5,	'Szüneteltetett',4),
        (6,	'Zárolt',5),
        (7,	'Felfüggesztett',3);",
        "INSERT INTO `admin_params` (`id`, `modul_azon`, `key`, `value`) VALUES
        (1,	'admin',	'system_name',	'Border'),
        (2,	'admin',	'title-background-color',	'#1e5999'),
        (3,	'admin',	'title-color',	'#ffffff'),
        (4,	'admin',	'higlight-text-color',	'#1e5999'),
        (5,	'admin',	'button-higlight-color',	'#ff8200'),
        (6,	'admin',	'main-size',	'3'),
        (7,	'admin',	'button-size',	'3'),
        (8,	'admin',	'main-family',	'Arial, sans-serif'),
        (9,	'admin',	'login_informative_text',	'A felhasználónak be kell tartania minden hazai előírást és szabályozást, amely a társaságra vonatkozik. Jogosulatlan használatnak tekinthető mindaz, ami a rendszerben szereplő adatok felhasználását sérti.'),
        (10,	'admin',	'login_company',	'HW Stúdió Kft.'),
        (11,	'admin',	'login_system_users',	'HW Stúdió Kft.'),
        (12,	'admin',	'password_size',	'8'),
        (13,	'admin',	'password_numeric_size',	'1'),
        (14,	'admin',	'password_capital_size',	'0'),
        (15,	'admin',	'password_spec_char',	'0'),
        (16,	'admin',	'password_user_last_paswords',	'0'),
        (17,	'admin',	'password_entry_requests',	'5'),
        (18,	'admin',	'password_entry_delay',	'5'),
        (19,	'admin',	'users_activate_days',	'15'),
        (20,	'admin',	'password_valid_days',	'0'),
        (21,	'admin',	'password_user_inactive_days',	'0'),
        (22,	'admin',	'user_inanctive_timeout',	'0'),
        (23,	'admin',	'maintenance_mode',	'N'),
        (24,	'admin',	'maintance_header',	NULL),
        (25,	'admin',	'maintance_text',	NULL),
        (26,	'admin',	'log_debug',	'N');",
    ];

    public $sql_down = [
        "ALTER TABLE admin_group_perms DROP FOREIGN KEY admin_group_perms_ibfk_1",
        "ALTER TABLE admin_group_perms DROP FOREIGN KEY admin_group_perms_ibfk_2",
        "ALTER TABLE admin_groups DROP FOREIGN KEY admin_groups_ibfk_1",
        "ALTER TABLE admin_user_entities DROP FOREIGN KEY admin_user_entities_ibfk_1",
        "ALTER TABLE admin_user_entities DROP FOREIGN KEY admin_user_entities_ibfk_2",
        "ALTER TABLE admin_user_groups DROP FOREIGN KEY admin_user_groups_ibfk_1",
        "ALTER TABLE admin_user_groups DROP FOREIGN KEY admin_user_groups_ibfk_2",
        "ALTER TABLE admin_user_perms DROP FOREIGN KEY admin_user_perms_ibfk_1",
        "ALTER TABLE admin_user_perms DROP FOREIGN KEY admin_user_perms_ibfk_2",
        "ALTER TABLE admin_users DROP FOREIGN KEY admin_users_ibfk_1",
        "DROP TABLE admin_documents",
        "DROP TABLE admin_entities",
        "DROP TABLE admin_group_perms",
        "DROP TABLE admin_groups",
        "DROP TABLE admin_params",
        "DROP TABLE admin_user_entities",
        "DROP TABLE admin_user_groups",
        "DROP TABLE admin_user_perms",
        "DROP TABLE admin_user_states",
        "DROP TABLE admin_users",
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