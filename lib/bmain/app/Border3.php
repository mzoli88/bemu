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
        app()->useStoragePath($path);
        Config::set('filesystems.disks.local.root', $path);
        // Config::set('path.storage', $path);


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

        $modul = config('mods')[$modul_azon];

        $jogok = [];
        $out = [];

        if (array_key_exists('perms', $modul)) {
            foreach ($modul['perms'] as $key => $value) $jogok[$key] = $modul['name'] . ' - ' . $value;
        }

        if (array_key_exists('menu', $modul)) {
            foreach ($modul['menu'] as $key => $value) $jogok[$key] = $modul['name'] . ' - ' . $value['name'] . ' (menüpont)';
        }

        if ($jogok) foreach ($jogok as $key => $value) {
            if ($value === true) {
                $out[$key] = true;
                continue;
            }

            if ($key === 'badmin') {
                $val = self::rendszergazda_e();
            } else {
                $val = self::queryJog($value);
            }
            $out[$key] = $val ? true : false;
        }

        Cache::set('user_perm_' . getUserId() . '_' . $modul_azon, $out);
        return $out;
    }

    static function getUserData($modul_azon = null)
    {
        $modul_azon = $modul_azon ?: getModulAzon();
        $jogok = self::setJogok($modul_azon);

        // $modul_data = self::getModulData($modul_azon)->first();
        $mods = config('mods');

        $menu = [];

        if (array_key_exists($modul_azon, $mods)) {
            if (isSysAdmin() && ($modul_azon == 'admin' || $modul_azon == 'naplo')) {
                $menu = $mods[$modul_azon]['menu'];
            } else {
                $menu = collect($mods[$modul_azon]['menu'])->filter(function ($menu, $key) use ($jogok) {
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
        // dd ($entities);

        if (count($entities) == 0) {
            sendError("Felhasználó nincsen entitáshoz kötve! Keressen fel egy rendszer adminisztrátort!");
        }

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

    static function queryJog($ellenorzendo, $user_id = false)
    {
        if ($user_id === false) $user_id = getUserId();

        $query = DB::table('nevek_csoportosit');
        $query->join('nevek_csoport', 'nevek_csoport.csoport_id', '=', 'nevek_csoportosit.csoport_id');
        $query->where('nevek_csoportosit.nevek_id', $user_id);
        $query->where('nevek_csoport.nev', conv($ellenorzendo));
        $result = $query->first();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    static function getJogId($jog)
    {
        if (is_array($jog)) {
            return DB::table('nevek_csoport')
                ->whereIn('nev', collect($jog)->map(function ($x) {
                    return conv($x);
                })->toArray())->pluck('csoport_id');
        } else {
            $one = DB::table('nevek_csoport')
                ->where(['nev' => conv($jog)])
                ->first();
            if ($one) {
                return $one->csoport_id;
            }
        }
        return false;
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

    //modul adatok lekérdezése (bármelyik modulé)
    static function getModulData($azon = false)
    {
        if ($azon === false) $azon =  getModulAzon();
        return DB::table('b_modulok')->where('azon', $azon);
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
}
