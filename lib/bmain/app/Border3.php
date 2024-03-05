<?php

namespace App;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Border3
{

    static function init()
    {
        // ini_set('default_charset', 'utf-8');
        // config(['isBorder' => true]);
        // Config::set('app.timezone', 'Europe/Budapest');
        // date_default_timezone_set('Europe/Budapest');

        // if (app()->runningInConsole()) {
        //     //ha cosole fut akkor it kell berántani a config-ot
        //     set_include_path(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'));
        //     //normál esetben a mod/modulnev/remote/index.php fájlában a belepve.php fáj-al szerezzük meg a configot
        //     @require_once('config.class.php');
        // }

        // if (defined('USE_ENTITY_' . strtoupper($modul_azon))) {
        //     $val = constant('USE_ENTITY_' . strtoupper($modul_azon));
        //     if ($val === true || $val === 'true') {
        //         $val = true;
        //     } else {
        //         $val = false;
        //     }
        //     Config::set('use_entity', $val);
        // } else {
        //     Config::set('use_entity', $use_entity);
        // }

        Config::set('modul_azon', getModulAzon());
        // dd (getModulAzon());

        $modul = config('mods')[getModulAzon()];
        $perms = [];

        if (array_key_exists('perms', $modul)) {
            foreach ($modul['perms'] as $key => $value) $perms[$key] = $modul['name'] . ' - ' . $value;
        }

        if (array_key_exists('menu', $modul)) {
            foreach ($modul['menu'] as $key => $value) $perms[$key] = $modul['name'] . ' - ' . $value['name'] . ' (menüpont)';
        }

        Config::set('permissions', $perms);




        Config::set('BORDER_PREFIX', BORDER_PREFIX);
        Config::set('BORDER_PATH_BORDER', BORDER_PATH_BORDER);
        Config::set('BORDER_PATH_BORDERDOC', BORDER_PATH_BORDERDOC);
        Config::set('BORDER_PATH_BORDERLIB', BORDER_PATH_BORDERLIB);

        //storage beállítás
        Config::set('filesystems.default', 'local');
        Config::set('filesystems.disks.local.root', BORDER_PATH_BORDERDOC . getModulAzon() . DIRECTORY_SEPARATOR);
        Config::set('path.storage', BORDER_PATH_BORDERDOC . getModulAzon() . DIRECTORY_SEPARATOR);

        // if (!is_dir(BORDER_PATH_BORDERDOC . $modul_azon)) mkdir(BORDER_PATH_BORDERDOC . $modul_azon);
        // $log_dir = BORDER_PATH_BORDERDOC . $modul_azon . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        // if (!is_dir($log_dir)) mkdir($log_dir);


        // if (@$_SESSION && isset($_SESSION['id'])) {

            // Config::set('user_id', (int)$_SESSION['id']);

            // Config::set('user_login', toUtf($_SESSION['nev']));
            // Config::set('user_name', toUtf($_SESSION['teljesnev']));

            // if (isset($_SESSION['uuid'])) {
            //     Config::set('uuid', $_SESSION['uuid']);
            // } else {
            //     $_SESSION['uuid'] = (string) Str::uuid();
            //     Config::set('uuid', $_SESSION['uuid']);
            // }

            // if (config('use_entity')) {

            //     if (!isset($_SESSION['entity_type_id'])) {
            //         $tmp = DB::table('b_nagycsoport_tipus')->select('b_nagycsoport_tipus_id')->where('tipusnev', 'Entitás')->first();
            //         if ($tmp) {
            //             $_SESSION['entity_type_id'] = $tmp->b_nagycsoport_tipus_id;
            //         } else {
            //             sendError('Entitás típus nem található!');
            //         }
            //     }
            //     Config::set('entity_type_id', $_SESSION['entity_type_id']);
            //     Config::set('active_entity', self::getActiveEntity());
            //     Config::set('user_entity', self::getUserEntity());
            // }

            // if (!isset($_SESSION['szervezeti_egyseg_id'])) {
            //     $tmp = DB::table('b_nagycsoport_tipus')->select('b_nagycsoport_tipus_id')->where('tipusnev', 'Szervezeti egység')->first();
            //     if ($tmp) {
            //         $_SESSION['szervezeti_egyseg_id'] = $tmp->b_nagycsoport_tipus_id;
            //     } else {
            //         sendError('Szervezeti egység típus nem található!');
            //     }
            // }
            // Config::set('szervezeti_egyseg_id', $_SESSION['szervezeti_egyseg_id']);
        // } 
        // else {
            // if (app()->runningInConsole() && !config('user_id')) {
            //     Config::set('user_id', '-');

            //     Config::set('user_login', '-');
            //     Config::set('user_name', 'Technikai felhasználó');
            //     Config::set('uuid', (string) Str::uuid());
            // }
        // }
    }

    static function send_error($txt, $code = 500, $title = false)
    {
        $out = ["message" => $txt];
        if ($title) $out['title'] = $title;
        response()->json($out, $code)->send();
        exit();
    }

    static function setJogok()
    {
        if (!key_exists('permissions', $_SESSION)) $_SESSION['permissions'] = [];
        $_SESSION['permissions'][config('modul_azon')] = [];
        $jogok = config('permissions');

        if ($jogok) foreach ($jogok as $key => $value) {
            if ($value === true) {
                $_SESSION['permissions'][config('modul_azon')][$key] = true;
                continue;
            }

            if ($key === 'badmin') {
                $val = self::rendszergazda_e();
            } else {
                $val = self::queryJog($value);
            }
            $_SESSION['permissions'][config('modul_azon')][$key] = $val ? true : false;
        }

        return $_SESSION['permissions'][config('modul_azon')];
    }

    static function getUserData()
    {
        $jogok = self::setJogok();
        $modul_data = self::getModulData()->first();

        // $entity_data = null;
        // if (config('use_entity')) {

        //     $user_csop = DB::table('b_nagycsoport')->select('b_nagycsoport_id as value', 'nev as name')
        //         ->where('b_nagycsoport_tipus_id', config('entity_type_id'))
        //         ->whereExists(function ($query) {
        //             $query->select()
        //                 ->from('b_nagycsoport_nevek')
        //                 ->where('b_nagycsoport_nevek.nevek_id', $_SESSION['id'])
        //                 ->whereColumn('b_nagycsoport_nevek.b_nagycsoport_id', 'b_nagycsoport.b_nagycsoport_id');
        //         })
        //         ->whereExists(function ($query) {
        //             $query->select()
        //                 ->from('b_nagycsoport_modul')
        //                 ->where('b_nagycsoport_modul.modul_azon', config('modul_azon'))
        //                 ->whereColumn('b_nagycsoport_modul.b_nagycsoport_id', 'b_nagycsoport.b_nagycsoport_id');
        //         })
        //         ->get();

        //     self::setUserEntity($user_csop->pluck('value')->toArray());

        //     if (isset($_GET['active_entity_id'])) {
        //         if (in_array($_GET['active_entity_id'], $user_csop->pluck('value')->toArray())) {
        //             self::setActiveEntity($_GET['active_entity_id']);
        //         }
        //     }
        //     //Ha csak egy entitáshoz vagyunk állítva, akkor az legyen az aktív
        //     if ($user_csop->count() == 1) {
        //         self::setActiveEntity($user_csop->first()->value);
        //     }
        //     $entity_list = $user_csop->toArray();
        //     foreach ($entity_list as $key => $entity) {
        //         $entity_list[$key]->name = toUtf($entity_list[$key]->name);
        //     }
        //     $entity_data = [
        //         'active' => self::getActiveEntity(),
        //         'list' => $entity_list,
        //     ];
        // }

        if (!$modul_data) return self::send_error("Modul verziója nincs telepítve!", 404);

        $menu = [];

        $mods = config('mods');

        if (isset($_GET['modul']) && array_key_exists($_GET['modul'], $mods)) {
            $menu = collect($mods[$_GET['modul']]['menu'])->filter(function ($menu, $key) {
                return hasPerm($key);
            });
        }
        if (count($menu) < 1) self::send_error("Nincs jogosultság menüpont eléréséhez!", 403);

        return [
            'id' => (int)$_SESSION['id'],
            'nev' => toUtf($_SESSION['nev']),
            'teljesnev' => toUtf($_SESSION['teljesnev']),
            'permissions' => $jogok,
            'modul_nev' => toUtf($modul_data->modulnev),
            'modul_verzio' => toUtf($modul_data->verzio),
            'modul_company' => date('Y') . ' HW Stúdió Kft.',
            'menu' => $menu,
            // 'entity_data' => $entity_data,
            // 'CacheQueue' => CacheQueue::isReady(),
        ];
    }

    static function setUserEntity($csop)
    {
        if (!isset($_SESSION['user_entity_ids'])) {
            $_SESSION['user_entity_ids'] = [];
        }
        Config::set('user_entity', $csop);
        $_SESSION['user_entity_ids'][config('modul_azon')] = $csop;
    }

    static function setActiveEntity($id)
    {
        if (!isset($_SESSION['active_entity_id'])) {
            $_SESSION['active_entity_id'] = [];
        }
        Config::set('active_entity', $id);
        $_SESSION['active_entity_id'][config('modul_azon')] = $id;
    }

    static function getActiveEntity()
    {
        if (!isset($_SESSION['active_entity_id'])) return false;
        if (!isset($_SESSION['active_entity_id'][config('modul_azon')])) return false;
        return $_SESSION['active_entity_id'][config('modul_azon')];
    }

    static function getUserEntity()
    {
        if (!isset($_SESSION['user_entity_ids']) || !isset($_SESSION['user_entity_ids'][config('modul_azon')])) {
            $user_csop = DB::table('b_nagycsoport')->select('b_nagycsoport_id as value', 'nev as name')
                ->where('b_nagycsoport_tipus_id', config('entity_type_id'))
                ->whereExists(function ($query) {
                    $query->select()
                        ->from('b_nagycsoport_nevek')
                        ->where('b_nagycsoport_nevek.nevek_id', $_SESSION['id'])
                        ->whereColumn('b_nagycsoport_nevek.b_nagycsoport_id', 'b_nagycsoport.b_nagycsoport_id');
                })
                ->whereExists(function ($query) {
                    $query->select()
                        ->from('b_nagycsoport_modul')
                        ->where('b_nagycsoport_modul.modul_azon', config('modul_azon'))
                        ->whereColumn('b_nagycsoport_modul.b_nagycsoport_id', 'b_nagycsoport.b_nagycsoport_id');
                })
                ->get();

            self::setUserEntity($user_csop->pluck('value')->toArray());
        }



        return $_SESSION['user_entity_ids'][config('modul_azon')];
    }

    static function queryJog($ellenorzendo, $user_id = false)
    {
        if ($user_id === false && isset($_SESSION)) $user_id = $_SESSION['id'];

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

        //Biztonsági okokból kérve lett, hogy mindig le legyen kérdezve a jogosultság
        if (!array_key_exists('permissions', $_SESSION) || !array_key_exists(config('modul_azon'), $_SESSION['permissions'])) {
            self::setJogok();
        }

        foreach ($tmp as $value) {
            if ($value == true) {
                $has_jog = true;
                break;
            }
            if ($_SESSION['permissions'][config('modul_azon')][$value] === true) {
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
        if ($user_id === false && isset($_SESSION)) $user_id = $_SESSION['id'];
        $result = DB::table('nevek_csoportosit')->select('nevek_id')->where('csoport_id', 2)->where("nevek_id", $user_id)->first();
        if ($result) return true;
        else false;
    }

    //modul adatok lekérdezése (bármelyik modulé)
    static function getModulData($azon = false)
    {
        if ($azon === false) $azon =  config('modul_azon');
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
