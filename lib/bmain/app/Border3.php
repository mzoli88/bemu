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
        $path = preg_replace('#\\'.DIRECTORY_SEPARATOR.'$#','',BORDER_PATH_BORDERDOC);
        app()->useStoragePath($path);
        Config::set('filesystems.disks.local.root', $path);

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

    static function getJogok()
    {
        //belépve ellenőrzés
        if (!getUser()) sendError("Nincs belépve!", 401);
        return Cache::get('user_perm_' . getUserId() . '_' . getModulAzon());
    }

    static function setJogok()
    {
        $modul_azon = getModulAzon();

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

        Cache::set('user_perm_' . getUserId() . '_' . $modul_azon,$out);
    }

    static function getUserData()
    {
        self::setJogok();
        $jogok = self::getJogok();
        $modul_data = self::getModulData()->first();

        if (!$modul_data) return self::send_error("Modul verziója nincs telepítve!", 404);

        $menu = [];

        $mods = config('mods');

        if (isset($_GET['modul']) && array_key_exists($_GET['modul'], $mods)) {
            $menu = collect($mods[$_GET['modul']]['menu'])->filter(function ($menu, $key) {
                return hasPerm($key);
            });
        }
        if (count($menu) < 1) self::send_error("Nincs jogosultság menüpont eléréséhez!", 403);

        $user = getUser();

        return [
            'id' => $user->id,
            'nev' => $user->login,
            'teljesnev' => $user->name,
            'permissions' => $jogok,
            'modul_nev' => toUtf($modul_data->modulnev),
            'modul_verzio' => toUtf($modul_data->verzio),
            'modul_company' => date('Y') . ' HW Stúdió Kft.',
            'menu' => $menu,
        ];
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
            if (array_key_exists($value,$jogok) && $jogok[$value] === true) {
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
