<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [
        "CREATE TABLE template_modul_groups (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', group_id INT NOT NULL COMMENT 'Szerepkör azonosító', modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', INDEX entity_id_modul_azon_group_id (entity_id, modul_azon, group_id), INDEX group_id (group_id), INDEX modul_azon (modul_azon), INDEX IDX_359E03CB81257D5D (entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Sablonkezelő szerepkör modul karbantartás jogok' ",
        "CREATE TABLE template_modul_users (id INT AUTO_INCREMENT NOT NULL COMMENT 'Azonosító', entity_id INT NOT NULL COMMENT 'Entitás azonosító', user_id BIGINT UNSIGNED NOT NULL COMMENT 'Felhasználó azonosító', modul_azon VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_hungarian_ci` COMMENT 'Modul azonosító', INDEX entity_id_modul_azon_user_id (entity_id, modul_azon, user_id), INDEX modul_azon (modul_azon), INDEX user_id (user_id), INDEX IDX_5D82A05181257D5D (entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_hungarian_ci` ENGINE = InnoDB COMMENT = 'Sablonkezelő felhasználó modul karbantartás jogok' ",
    ];

    public $sql_down = [
        "ALTER TABLE template_modul_groups DROP FOREIGN KEY template_modul_groups_ibfk_1",
        "ALTER TABLE template_modul_groups DROP FOREIGN KEY template_modul_groups_ibfk_2",
        "ALTER TABLE template_modul_users DROP FOREIGN KEY template_modul_users_ibfk_1",
        "ALTER TABLE template_modul_users DROP FOREIGN KEY template_modul_users_ibfk_2",
        "DROP TABLE template_modul_groups",
        "DROP TABLE template_modul_users",
    ];

    public function up()
    {
        foreach ($this->sql_up as $sq) {
            DB::statement($sq);
        }
        if (!config('isBorder')) {
            DB::statement("ALTER TABLE template_modul_groups ADD CONSTRAINT template_modul_groups_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
            DB::statement("ALTER TABLE template_modul_groups ADD CONSTRAINT template_modul_groups_ibfk_2 FOREIGN KEY (group_id) REFERENCES admin_groups (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
            DB::statement("ALTER TABLE template_modul_users ADD CONSTRAINT template_modul_users_ibfk_1 FOREIGN KEY (entity_id) REFERENCES admin_entities (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
            DB::statement("ALTER TABLE template_modul_users ADD CONSTRAINT template_modul_users_ibfk_2 FOREIGN KEY (user_id) REFERENCES admin_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
        }
    }

    public function down()
    {
        foreach ($this->sql_down as $sq) {
            DB::statement($sq);
        }
    }
};
