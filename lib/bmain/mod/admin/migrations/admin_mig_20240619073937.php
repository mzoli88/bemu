<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{

    public $sql_up = [

        "drop view if exists admin_users",
        "CREATE VIEW admin_users AS
            SELECT id, teljesnev as name, nev as login, email, 2 as state_id
            FROM nevek
            where belephet = 1",

        "drop view if exists admin_groups",
        "CREATE VIEW admin_groups AS
            SELECT b_nagycsoport_id as id, nev as name, 'I' as status
            FROM b_nagycsoport
            where b_nagycsoport_tipus_id in (1,2)",

        "drop view if exists admin_entities",
        "CREATE VIEW admin_entities AS
            SELECT b_nagycsoport_id as id, nev as name, 'I' as status
            FROM b_nagycsoport
            where b_nagycsoport_tipus_id = (select b_nagycsoport_tipus_id from b_nagycsoport_tipus where tipusnev = 'Entitás')",

        "drop view if exists admin_user_perms",
        "CREATE VIEW admin_user_perms AS
            SELECT up.nevek_csoportosit_id as id, up.nevek_id as user_id, p.modul_azon as modul_azon, p.menu_azon as perm, p.csoport_id as perm_id
            FROM nevek_csoportosit as up
            inner join nevek_csoport as p on p.csoport_id = up.csoport_id
            where p.modul_azon IS NOT NULL",

        "drop view if exists admin_user_groups",
        "CREATE VIEW admin_user_groups AS
            SELECT b_nagycsoport_nevek_id as id, nevek_id as user_id, b_nagycsoport_id as group_id
            FROM b_nagycsoport_nevek",

        "drop view if exists admin_group_perms",
        "CREATE VIEW admin_group_perms AS
            SELECT b_nagycsoport_nevekcsop_id as id, b_nagycsoport_id as group_id, p.modul_azon as modul_azon, p.menu_azon as perm, p.csoport_id as perm_id
            FROM b_nagycsoport_nevekcsop as up
            inner join nevek_csoport as p on p.csoport_id = up.nevek_csoport_id
            where p.modul_azon = false", //where p.modul_azon IS NOT NULL; //ha nem üres táblát szeretnénk

        "drop view if exists admin_user_entities",
        "CREATE VIEW admin_user_entities AS
            SELECT b_nagycsoport_nevek_id as id, nevek_id as user_id, b_nagycsoport_id as entity_id
            FROM b_nagycsoport_nevek
            where b_nagycsoport_id in ( select id from admin_entities )",

        "drop view if exists admin_perms",
        "CREATE VIEW admin_perms AS 
            select csoport_id as id,
                modul_azon,
                menu_azon as perm,
                nev as name
                from `nevek_csoport`",

        "drop view if exists admin_view_perms",
        "CREATE VIEW admin_view_perms AS 
        (
            select 
            `admin_group_perms`.`modul_azon`,
            `admin_group_perms`.`perm`,
            `Rel_user_group`.`user_id` as `user_id`,
            `admin_group_perms`.`perm_id`
            from `admin_group_perms`
            left join `admin_user_groups` as `Rel_user_group` on `admin_group_perms`.`group_id` = `Rel_user_group`.`group_id`
        ) union (
            select 
            `admin_user_perms`.`modul_azon`,
            `admin_user_perms`.`perm`,
            `admin_user_perms`.`user_id`,
            `admin_user_perms`.`perm_id`
            from `admin_user_perms`
        )",

    ];

    public $sql_down = [];

    public function up()
    {
        if (Schema::hasTable('nevek_csoport')) {
            foreach ($this->sql_up as $sq) {
                DB::statement($sq);
            }
        }
    }

    public function down()
    {
        foreach ($this->sql_down as $sq) {
            DB::statement($sq);
        }
    }
};
