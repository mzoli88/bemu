<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Border3
{

    static function init()
    {

        //storage beállítás
        $path = BORDER_PATH_BORDERDOC . getModulAzon();
        // app()->useStoragePath($path);
        Config::set('filesystems.disks.local.root', $path);
        Config::set('path.storage', $path);


        // adatbázis konfiguráció
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => BORDER_DB_HOST,
            'database' => BORDER_DB_DATABASE,
            'username' => BORDER_DB_USER,
            'password' => BORDER_DB_PASSWORD,
        ]);
    }

    static function send_error($txt, $code = 500, $title = false)
    {
        $out = ["message" => $txt];
        if ($title) $out['title'] = $title;
        response()->json($out, $code)->send();
        exit();
    }

    static function getJogok($modul_azon = null)
    {
        $modul_azon = $modul_azon ?: getModulAzon();
        //belépve ellenőrzés
        if (!getUser()) sendError("Nincs belépve!", 401);
        return Cache::get('user_perm_' . getUserId() . '_' . $modul_azon);
    }

    static function setJogok($modul_azon = null)
    {
        $modul_azon = $modul_azon ?: getModulAzon();

        $out = [];

        $out = DB::table('nevek_csoport')
            ->selectRaw("menu_azon, CASE WHEN EXISTS (
                SELECT nevek_csoportosit_id FROM nevek_csoportosit WHERE nevek_csoportosit.nevek_id = " . getUserId() . " AND nevek_csoport.csoport_id = nevek_csoportosit.csoport_id
            ) THEN 'I' ELSE 'N' END AS has_jog")
            ->where('modul_azon', $modul_azon)
            ->get()
            ->keyBy('menu_azon')
            ->map(function ($v) {
                return $v->has_jog == 'I';
            })
            ->toArray();

        $out['sys_admin'] = self::rendszergazda_e();

        Cache::set('user_perm_' . getUserId() . '_' . $modul_azon, $out);
        return $out;
    }

    static function getUserData($modul_azon = null)
    {
        $modul_azon = $modul_azon ?: getModulAzon();
        $jogok = self::setJogok($modul_azon);

        $mods = config('mods');

        $menu = [];

        if (array_key_exists($modul_azon, $mods)) {
            if (isSysAdmin() && ($modul_azon == 'admin' || $modul_azon == 'naplo')) {
                $menu = $mods[$modul_azon]['menu'];
            } else {
                $menu = collect($mods[$modul_azon]['menu'])->filter(function ($menu, $key) use ($jogok) {
                    if (array_key_exists('noprem', $menu)) return true;
                    if (!array_key_exists($key, $jogok)) return false;
                    return $jogok[$key];
                })->toArray();
            }
        }
        if (count($menu) < 1) self::send_error("Nincs jogosultság menüpont eléréséhez!", 403);

        $user = getUser();

        return [
            'id' => $user->id,
            'nev' => $user->login,
            'teljesnev' => $user->name,
            'perms' => $jogok,
            'modul_nev' => $mods[$modul_azon]['name'],
            'modul_verzio' => $mods[$modul_azon]['version'],
            'modul_company' => date('Y') . ' HW Stúdió Kft.',
            'menu' => $menu,
            'sys_admin' => isSysAdmin() ? 'I' : 'N',
            'entities' => self::getUserEntitys(),
            'active_entity' => self::getDefaultEntityId(),
        ];
    }

    static function getDefaultEntityId()
    {

        $entity = getEntity();

        if (!empty($entity) && $entity != 0) return $entity;

        $entities = self::getUserEntityIds();

        // if (count($entities) == 0) {
        //     kivettem, hogy ne dobjon hibaüzenetet olyan modulban ahol nincs entitás kezelés
        //     sendError("Felhasználó nincsen entitáshoz kötve! Keressen fel egy rendszer adminisztrátort!");
        // }

        return array_shift($entities);
    }

    static function getEntityTypeId()
    {
        // Cache::forget('entity_type_id');
        return Cache::rememberForever('entity_type_id', function () {
            $result = DB::table('b_nagycsoport_tipus')->select('b_nagycsoport_tipus_id')->where('tipusnev', 'Entitás')->first();
            if (!$result) DB::table('b_nagycsoport_tipus')->insert(['tipusnev' => 'Entitás']);
            $type_id = DB::table('b_nagycsoport_tipus')->select('b_nagycsoport_tipus_id')->where('tipusnev', 'Entitás')->first()->b_nagycsoport_tipus_id;
            $result = DB::table('b_nagycsoport')->select('b_nagycsoport_id')->where('b_nagycsoport_tipus_id', $type_id)->first();
            if (!$result) DB::table('b_nagycsoport')->insert(['nev' => 'Entitás', 'b_nagycsoport_tipus_id' => $type_id]);
            return $type_id;
        });
    }

    static function getEntities()
    {
        // Cache::forget('entities');
        return Cache::remember('entities', now()->addMinutes(10), function () {
            return DB::table('b_nagycsoport')
                ->select('b_nagycsoport_id as value', 'nev as name')
                ->where('b_nagycsoport_tipus_id', self::getEntityTypeId())
                ->get()->keyBy('value')
                ->map(function ($v) {
                    return (array)$v;
                })
                ->toArray();
        });
    }

    static function getUserEntityIds($user_id = null)
    {
        $user_id = $user_id ?: getUserId();
        // Cache::forget('user_entities_' . $user_id);
        // return Cache::remember('user_entities_' . $user_id, now()->addMinutes(10), function () use ($user_id) {
        return collect(self::getUserEntitys($user_id))->pluck('value')->toArray();
        // });
    }

    static function getUserEntitys($user_id = null)
    {

        $user_id = $user_id ?: getUserId();

        if (hasJustOneEntity()) return entities();

        return DB::table('b_nagycsoport')->select('b_nagycsoport_id as value', 'nev as name')
            ->where('b_nagycsoport_tipus_id', self::getEntityTypeId())
            ->whereExists(function ($query) use ($user_id) {
                $query->select()
                    ->from('b_nagycsoport_nevek')
                    ->where('b_nagycsoport_nevek.nevek_id', $user_id)
                    ->whereColumn('b_nagycsoport_nevek.b_nagycsoport_id', 'b_nagycsoport.b_nagycsoport_id');
            })
            // ->whereExists(function ($query) {
            //     $query->select()
            //         ->from('b_nagycsoport_modul')
            //         ->where('b_nagycsoport_modul.modul_azon', getModulAzon())
            //         ->whereColumn('b_nagycsoport_modul.b_nagycsoport_id', 'b_nagycsoport.b_nagycsoport_id');
            // })
            ->get();
    }

    static function getJog($tmp)
    {
        if (!is_array($tmp)) {
            $tmp = func_get_args();
        }
        $has_jog = false;

        $jogok = self::getJogok();

        foreach ($tmp as $value) {
            if ($value === true) {
                $has_jog = true;
                break;
            }
            if ($value == 'SysAdmin' && isSysAdmin()) {
                $has_jog = true;
                break;
            }
            if (array_key_exists($value, $jogok) && $jogok[$value] === true) {
                $has_jog = true;
                break;
            }
        }
        return $has_jog;
    }

    static function checkJog($jog)
    {
        if ($jog === false || !call_user_func_array([Border3::class, 'getJog'], func_get_args())) {
            self::send_error("Nincs Jogosultság!", 403);
        }
    }

    /*ellenőrizze le, hogy a felhasználó rendszergazda-e*/
    static function rendszergazda_e($user_id = false)
    {
        if ($user_id === false) $user_id = getUserId();
        $result = DB::table('nevek_csoportosit')->select('nevek_id')->where('csoport_id', 2)->where("nevek_id", $user_id)->first();
        if ($result) return true;
        else false;
    }

    static function borderMail($cimzett_id, $msg, $felado_neve = null)
    {
        if (!is_array($cimzett_id)) $cimzett_id = [$cimzett_id];
        // b_alap::uzenet(" ", $cimzett_id, iconv('UTF-8','ISO-8859-1',$msg),1,iconv("UTF-8","ISO-8859-2",$felado_neve_vagy_id));

        collect($cimzett_id)->each(function ($cimzett_id) use ($felado_neve, $msg) {
            DB::table('b_uzenet')->insert([
                'datum' => date('Y-m-d H:i:s'),
                'tipus' => '1',
                'cel_nevek_id' => $cimzett_id,
                'cimzett' => DB::table('nevek')->where('id', $cimzett_id)->first()->teljesnev,
                'forras' => '-',
                'felado' => $felado_neve ?: '-',
                'uzenet' => $msg,
            ]);
        });
    }

    static function update_border($admin = false)
    {
        $mods = include base_path('config/mods.php');
        collect($mods)->each(function ($modul, $modul_azon) use ($admin) {
            if ($admin == false && $modul_azon == 'admin') return;

            //jogok feltöltése       
            $perms = [];
            if (array_key_exists('perms', $modul)) {
                foreach ($modul['perms'] as $key => $value) $perms[$key] = $modul['name'] . ' - ' . $value;
            }
            if (array_key_exists('menu', $modul)) {
                foreach ($modul['menu'] as $key => $value) $perms[$key] = $modul['name'] . ' - ' . $value['name'] . ' (menüpont)';
            }

            // dump($perms);

            collect($perms)->each(function ($perm_name, $menu_azon) use ($modul_azon) {
                $result = DB::table('nevek_csoport')
                    ->where('modul_azon', $modul_azon)
                    ->where('menu_azon', $menu_azon)
                    ->first();
                if ($result) {
                    if ($result->nev != $perm_name) {
                        DB::table('nevek_csoport')
                            ->where('csoport_id', $result->csoport_id)
                            ->update(['nev' => toLatin($perm_name)]);
                    }
                } else {
                    DB::table('nevek_csoport')->insert([
                        'nev' => toLatin($perm_name),
                        'modul_azon' => $modul_azon,
                        'menu_azon' => $menu_azon,
                    ]);
                }
            });

            // jogok törlése
            DB::table('nevek_csoport')
                ->where('modul_azon', $modul_azon)
                ->whereNotIn('menu_azon', array_keys($perms))
                ->get()->each(function ($to_delete) {
                    DB::table('nevek_csoportosit')
                        ->where('csoport_id', $to_delete->csoport_id)
                        ->delete();
                    DB::table('nevek_csoport')
                        ->where('csoport_id', $to_delete->csoport_id)
                        ->delete();
                });

            //Menüpontok feltöltése
            if (array_key_exists('menu', $modul)) {

                $main_menu = DB::table('b_menu')
                    ->where('modul_azon', $modul_azon)
                    ->where('id_csoport', 0)
                    ->whereNull('menu_azon')
                    ->first();

                if (!$main_menu) {
                    DB::table('b_menu')->insert([
                        'id_csoport' => 0,
                        'item' => 0,
                        'menu' => toLatin($modul['name']),
                        'link' => '',
                        'feltime' => time(),
                        'feltolto' => getUserId(),
                        'modul_azon' => $modul_azon,
                        // 'menu_azon' => $menu_azon,
                    ]);
                    $main_menu = DB::table('b_menu')
                        ->where('modul_azon', $modul_azon)
                        ->where('id_csoport', 0)
                        ->whereNull('menu_azon')
                        ->first();
                }

                //név változás miatt update ha kell
                if (toUtf($main_menu->menu) != $modul['name']) {
                    DB::table('b_menu')
                        ->where('id', $main_menu->id)
                        ->update(['menu' => toLatin($modul['name'])]);
                }


                $count = 1;

                foreach ($modul['menu'] as $key => $value) {
                    $almenu_name = $count . ' - ' . $value['name'];
                    $count++;

                    $jog_id = DB::table('nevek_csoport')->where('modul_azon', $modul_azon)->where('menu_azon', $key)->first()->csoport_id;

                    $hasjog = DB::table('b_menucsop')
                        ->where('menu_id', $main_menu->id)
                        ->where('csoport_id', $jog_id)
                        ->first();

                    if (!$hasjog) {
                        DB::table('b_menucsop')->insert([
                            'menu_id' => $main_menu->id,
                            'csoport_id' => $jog_id,
                            'csop_e' => 1,
                            'feltolthet' => 0,
                        ]);
                    }

                    $almenu = DB::table('b_menu')
                        ->where('modul_azon', $modul_azon)
                        ->where('menu_azon', $key)
                        ->first();

                    if (!$almenu) {
                        DB::table('b_menu')->insert([
                            'id_csoport' => $main_menu->id,
                            'item' => 1,
                            'menu' => toLatin($almenu_name),
                            'link' => 'mod/bmain/public/#' . $modul_azon . '.' . $key,
                            'feltime' => time(),
                            'feltolto' => getUserId(),
                            'modul_azon' => $modul_azon,
                            'menu_azon' => $key,
                        ]);
                        $almenu = DB::table('b_menu')
                            ->where('modul_azon', $modul_azon)
                            ->where('menu_azon', $key)
                            ->first();
                    }

                    //almenü jogosultság létrehozása
                    $hasjog2 = DB::table('b_menucsop')
                        ->where('menu_id', $almenu->id)
                        ->where('csoport_id', $jog_id)
                        ->first();

                    if (!$hasjog2) {
                        DB::table('b_menucsop')->insert([
                            'menu_id' => $almenu->id,
                            'csoport_id' => $jog_id,
                            'csop_e' => 1,
                            'feltolthet' => 0,
                        ]);
                    }


                    //név változás miatt update ha kell
                    if (toUtf($almenu->menu) != $almenu_name) {
                        DB::table('b_menu')
                            ->where('id', $almenu->id)
                            ->update(['menu' => toLatin($almenu_name)]);
                    }
                }

                // felesleges menüpontok törlése
                DB::table('b_menu')
                    ->where('modul_azon', $modul_azon)
                    ->whereNotIn('menu_azon', array_keys($modul['menu']))
                    ->get()->each(function ($to_delete) {
                        DB::table('b_menucsop')->where('menu_id', $to_delete->id)->delete();
                        DB::table('b_menu')->where('id', $to_delete->id)->delete();
                    });
            }
        });
    }
}
