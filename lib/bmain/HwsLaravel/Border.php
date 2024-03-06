<?php

namespace hws;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\JsonResponse;

function hasPerm($jog)
{
    if ($jog === false || !call_user_func_array([Border::class, 'getJog'], func_get_args())) {
        return false;
    }
    return true;
}

function getEntity()
{
    return Border::getActiveEntity();
}

class Border
{

    public function __construct(String $modul_azon, array $permissions = [], $use_entity = false)
    {

        config(['isBorder' => true]);
        Config::set('app.timezone', 'Europe/Budapest');
        date_default_timezone_set('Europe/Budapest');

        if (app()->runningInConsole()) {
            //ha cosole fut akkor it kell berántani a config-ot
            set_include_path(realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'));
            //normál esetben a mod/modulnev/remote/index.php fájlában a belepve.php fáj-al szerezzük meg a configot
            @require_once('config.class.php');
        }
        @require_once('naplo/Fnaplo.php');

        if (config('BORDER_PATH_BORDERLIB', false)) {
            if (config('BORDER_PATH_BORDERLIB') !== BORDER_PATH_BORDERLIB) {
                if (!app()->runningInConsole()) {
                    //végtelen ciklus lenne, ha consolbn is futna
                    self::bexec('optimize');
                    header("Refresh:2");
                    exit;
                }
            }
        }

        if (defined('USE_ENTITY_' . strtoupper($modul_azon))) {
            $val = constant('USE_ENTITY_' . strtoupper($modul_azon));
            if ($val === true || $val === 'true') {
                $val = true;
            } else {
                $val = false;
            }
            Config::set('use_entity', $val);
        } else {
            Config::set('use_entity', $use_entity);
        }

        new \Fnaplo($modul_azon);

        Config::set('modul_azon', $modul_azon);
        Config::set('permissions', $permissions);
        Config::set('BORDER_PREFIX', BORDER_PREFIX);
        Config::set('BORDER_PATH_BORDER', BORDER_PATH_BORDER);
        Config::set('BORDER_PATH_BORDERDOC', BORDER_PATH_BORDERDOC);
        Config::set('BORDER_PATH_BORDERLIB', BORDER_PATH_BORDERLIB);

        config([
            "logging" => [
                "default" => "naplo",
                "channels" => [
                    "naplo" => [
                        'driver' => 'custom',
                        'via' => NaploLogger::class,
                    ]
                ]
            ]
        ]);

        // adatbázis konfiguráció
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => BORDER_DB_HOST,
            'database' => BORDER_DB_DATABASE,
            'username' => BORDER_DB_USER,
            'password' => BORDER_DB_PASSWORD,
        ]);

        //storage beállítás
        Config::set('filesystems.default', 'local');
        Config::set('filesystems.disks.local.root', BORDER_PATH_BORDERDOC . $modul_azon . DIRECTORY_SEPARATOR);

        if (@$_SESSION && isset($_SESSION['id'])) {
            Config::set('user_id', (int)$_SESSION['id']);

            if (config('use_entity')) {

                if (!isset($_SESSION['entity_type_id'])) {
                    $tmp = DB::table('b_nagycsoport_tipus')->select('b_nagycsoport_tipus_id')->where('tipusnev', 'Entitás')->first();
                    if ($tmp) {
                        $_SESSION['entity_type_id'] = $tmp->b_nagycsoport_tipus_id;
                    } else {
                        sendError('Entitás típus nem található!');
                    }
                }
                Config::set('entity_type_id', $_SESSION['entity_type_id']);
                Config::set('active_entity', self::getActiveEntity());
                Config::set('user_entity', self::getUserEntity());
            }

            if (!isset($_SESSION['szervezeti_egyseg_id'])) {
                $tmp = DB::table('b_nagycsoport_tipus')->select('b_nagycsoport_tipus_id')->where('tipusnev', 'Szervezeti egység')->first();
                if ($tmp) {
                    $_SESSION['szervezeti_egyseg_id'] = $tmp->b_nagycsoport_tipus_id;
                } else {
                    sendError('Szervezeti egység típus nem található!');
                }
            }
            Config::set('szervezeti_egyseg_id', $_SESSION['szervezeti_egyseg_id']);
        } else {
            Config::set('user_id', false);
        }

        //ha debug bevam kapcsolva, akkor sql-t naplózzon.
        if (\Fnaplo::can('debug')) {
            DB::listen(function ($query) {
                if (empty($query->bindings)) {
                    logger()->debug("SQL_LOG:\n" . $query->sql);
                } else {
                    logger()->debug("SQL_LOG:\n" . vsprintf(str_replace('?', '%s', $query->sql), array_map(function ($v) {
                        if (is_string($v)) $v = '"' . $v . '"';
                        return $v;
                    }, $query->bindings)));
                }
            });
            Event::listen(RequestHandled::class, function ($event) {
                if ($event->response instanceof JsonResponse) {
                    $ct = $event->response->content();
                    //kivágjuk a file tartalmakat
                    \Fnaplo::cutFile($ct);
                    logger()->debug("RESPONSE_LOG: \n" . $ct);
                }
            });
        }
    }

    static function bexec($cmd, $do_in_background = true)
    {
        //háttérben futtatott console command
        $cmd = "php " . base_path() . DIRECTORY_SEPARATOR . "artisan " . $cmd;
        if (substr(php_uname(), 0, 7) == "Windows") {
            if ($do_in_background) {
                pclose(popen("start /B " . $cmd, "r"));
            } else {
                exec($cmd);
            }
        } else {
            if ($do_in_background) {
                exec($cmd . " > /dev/null &");
            } else {
                exec($cmd);
            }
        }
    }

    static function belepve()
    {
        if (empty(session_id())) {
            session_name('SESS_' . config('BORDER_PREFIX') . 'ID');
            session_start();
        }
        if (empty($_SESSION['id'])) self::send_error("Nincs belépve!", 401);
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
        self::belepve();
        if (!key_exists('permissions', $_SESSION)) $_SESSION['permissions'] = [];
        $_SESSION['permissions'][config('modul_azon')] = [];
        $jogok = config('permissions');
        $count = 0;
        if ($jogok) foreach ($jogok as $key => $value) {
            if ($value === true) {
                $_SESSION['permissions'][config('modul_azon')][$key] = true;
                $count++;
                continue;
            }
            if ($value === 'borderadmin') {
                $val = self::rendszergazda_e();
            } else {
                $val = self::queryJog($value);
            }
            $_SESSION['permissions'][config('modul_azon')][$key] = $val ? true : false;
            if ($val) $count++;
        }
        if ($count < 1) self::send_error("Nincs Jogosultság!", 403);

        return $_SESSION['permissions'][config('modul_azon')];
    }

    static function getUserData()
    {
        $jogok = self::setJogok();
        $modul_data = self::getModulData()->first();

        $entity_data = null;

        if (config('use_entity')) {

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

            if (isset($_GET['active_entity_id'])) {
                if (in_array($_GET['active_entity_id'], $user_csop->pluck('value')->toArray())) {
                    self::setActiveEntity($_GET['active_entity_id']);
                }
            }
            //Ha csak egy entitáshoz vagyunk állítva, akkor az legyen az aktív
            if ($user_csop->count() == 1) {
                self::setActiveEntity($user_csop->first()->value);
            }
            $entity_list = $user_csop->toArray();
            foreach ($entity_list as $key => $entity) {
                $entity_list[$key]->name = toUtf($entity_list[$key]->name);
            }
            $entity_data = [
                'active' => self::getActiveEntity(),
                'list' => $entity_list,
            ];
        }

        if (!$modul_data) {
            if (defined('BORDER_EMU')) {
                DB::table('b_modulok')->insert([
                    'azon' => config('modul_azon'),
                    'modulnev' => config('modul_azon'),
                    'verzio' => 'v1.0.0',
                ]);
                $modul_data = self::getModulData()->first();
            } else {
                return self::send_error("Modul verziója nincs telepítve!", 404);
            }
        }
        return [
            'id' => $_SESSION['id'],
            'nev' => toUtf($_SESSION['nev']),
            'teljesnev' => toUtf($_SESSION['teljesnev']),
            'permissions' => $jogok,
            'modul_nev' => toUtf($modul_data->modulnev),
            'modul_verzio' => toUtf($modul_data->verzio),
            'modul_company' => date('Y') . ' HW Stúdió Kft.',
            'entity_data' => $entity_data,
            'CacheQueue' => CacheQueue::isReady(),
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

        if (defined('BORDER_EMU')) {
            $ellenorzendo = conv($ellenorzendo);
            $equery = DB::table('nevek_csoport')->where('nev', $ellenorzendo);
            $result = $equery->first();
            if (!$result) {
                DB::table('nevek_csoport')->insert([
                    'nev' => $ellenorzendo
                ]);
            }
        }

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
        self::belepve();
        if (!is_array($tmp)) {
            $tmp = func_get_args();
        }
        $has_jog = false;

        if (!array_key_exists('permissions', $_SESSION) || !array_key_exists(config('modul_azon'), $_SESSION['permissions'])) {
            self::setJogok();
        }

        foreach ($tmp as $value) {
            if ($_SESSION['permissions'][config('modul_azon')][$value] === true) {
                $has_jog = true;
                break;
            }
        }
        return $has_jog;
    }

    static function checkJog($jog)
    {
        if ($jog === false || !call_user_func_array([Border::class, 'getJog'], func_get_args())) {
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

    static function nyk2($sablon_id, $adatTmb = [], $save = false)
    {
        // nyomtatvány sablon beállítása id alapján
        $fejparam = (array)DB::table('nyk2_fej')
            ->from('nyk2_fej')
            ->where(['nyk2_fej_id' => $sablon_id])
            ->first();
        if (empty($fejparam)) {
            logger()->error('Nyomtatvány nem hozható létre, mert nyomtatvány nem létezik! nyk_id=' . $sablon_id);
            return false;
        }
        $defult_param = (array) DB::table('nyk2_pdf_parameterek')->where(['id' => 1])->first();

        // alapértelmezett margók beállítása, ha nincs margó a sablonhoz
        if (!isset($fejparam['margo_fent'])) $fejparam['margo_fent'] = 10;
        if (!isset($fejparam['margo_jobb'])) $fejparam['margo_jobb'] = 10;
        if (!isset($fejparam['margo_lent'])) $fejparam['margo_lent'] = 10;
        if (!isset($fejparam['margo_bal'])) $fejparam['margo_bal'] = 10;
        if (!isset($fejparam['allo'])) $fejparam['allo'] = 1;
        //PDF-hez fejlec es lablec tagok definialasa
        if ($fejparam['egyedi_fejlec_aktiv'] === '1') {
            if (array_key_exists('egyedi_fejlec_tartalom', $fejparam)) $adatTmb['fejlec'] = $fejparam['egyedi_fejlec_tartalom'];
            if (array_key_exists('egyedi_lablec_tartalom', $fejparam)) $adatTmb['lablec'] = $fejparam['egyedi_lablec_tartalom'];
        } else {
            if (array_key_exists('fejlec_tartalom', $defult_param)) $adatTmb['fejlec'] = $defult_param['fejlec_tartalom'];
            if (array_key_exists('lablec_tartalom', $defult_param)) $adatTmb['lablec'] = $defult_param['lablec_tartalom'];
        }
        $fejparam['allo'] = $fejparam['allo'] == 0 ? 'LANDSCAPE' : 'PORTRAIT';
        $fejparam['papir_meret'] = $fejparam['papir_meret']  ?: 'A4';

        // html tartalom létrehozása, saját adatok betöltése
        $tartalom = '<div>' . invertConv($fejparam['doc_tpl'] ?: '') . '</div>';
        if (array_key_exists('egyedi_fejlec_aktiv', $fejparam) && $fejparam['egyedi_fejlec_aktiv'] == 1) {
            //fejléc			
            $tartalom = '<div><pd4ml:page.header>' . $fejparam['egyedi_fejlec_tartalom'] . '<br /></pd4ml:page.header></div>' . $tartalom;
            $tartalom .= '<div><pd4ml:page.footer>' . $fejparam['egyedi_lablec_tartalom'] . '</pd4ml:page.footer></div>';
        }

        //nyk2 kép url helyre tétele
        // $tartalom = str_replace('<img src="', '<img src="../../mod/nyk2/', $tartalom);
        $tartalom = str_replace('<img src="', '<img src="' . str_replace('\\', '/', preg_replace('/.*\:/', '', BORDER_PATH_BORDER)) . 'mod/nyk2/', $tartalom);
        collect($adatTmb)->each(function ($v, $k) use (&$adatTmb) {
            $adatTmb['$' . $k] = $v;
        });
        $tartalom = helyettesit($tartalom, $adatTmb);
        // dd($tartalom);
        $tartalom = '<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
body,td {
	font-size: 10pt;
	font-family: Arial, Helvetica, sans-serif;
	margin: 0;
}
.hint_hide {display: none}
.alap{
	padding-top: 5.5cm;
	padding-right: 2cm;
	padding-bottom: 4.5cm;
	padding-left: 3cm;
}
.gomb{
	font:9pt Arial, Helvetica, sans-serif;
	margin:0;
}
.style1 {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10pt;
}
.style12 {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 11pt;
}
.style3 {
	font-size: 10pt;
	font-weight: bold;
	font-family: Arial, Helvetica, sans-serif;
}
.style32 {
	font-size: 11pt;
	font-weight: bold;
	font-family: Arial, Helvetica, sans-serif;
}
.stylem {
	font-size: 12pt;
	font-weight: bold;
	font-family: Arial, Helvetica, sans-serif;
}
.style5 {font-size: 11pt; font-family: Arial, Helvetica, sans-serif; }
.bal-keret{
	border-left:1px solid #000000;
    border-collapse:collapse;
}
.jobb-keret{
	border-right:1px solid #000000;
    border-collapse:collapse;
}
.also-keret{
	border-bottom:1px solid #000000;
    border-collapse:collapse;
}
.felso-keret{
	border-top:1px solid #000000;
    border-collapse:collapse;
}
.bal-also-keret{
	border-bottom:1px solid #000000;
	border-left:1px solid #000000;
    border-collapse:collapse;
}
.bal-felso-keret{
	border-left:1px solid #000000;
	border-top:1px solid #000000;
    border-collapse:collapse;
}
.jobb-also-keret{
	border-right:1px solid #000000;
	border-bottom:1px solid #000000;
    border-collapse:collapse;
}
.jobb-felso-keret{
	border-right:1px solid #000000;
	border-top:1px solid #000000;
    border-collapse:collapse;
}
.bal-also-jobb-keret{
	border-bottom:1px solid #000000;
	border-left:1px solid #000000;
	border-right:1px solid #000000;
    border-collapse:collapse;
}
.bal-also-felso-keret{
	border-bottom:1px solid #000000;
	border-left:1px solid #000000;
	border-top:1px solid #000000;
    border-collapse:collapse;
}
.bal-jobb-felso-keret{
	border-left:1px solid #000000;
	border-top:1px solid #000000;
	border-right:1px solid #000000;
    border-collapse:collapse;
}
.jobb-also-felso-keret{
	border-right:1px solid #000000;
	border-bottom:1px solid #000000;
	border-top:1px solid #000000;
    border-collapse:collapse;
}
.also-felso-keret{
	border-bottom:1px solid #000000;
	border-top:1px solid #000000;
    border-collapse:collapse;
}
.bal-jobb-keret{
	border-left:1px solid #000000;
	border-right:1px solid #000000;
    border-collapse:collapse;
}
.keret {
	border: 1px solid #000;
    border-collapse:collapse;
}
.szamlaszam{
	font-size:13pt;
	font-weight:bold;
	margin:0.5em;
	letter-spacing:2pt;
}
.bankkartyaszam{
	font-size:13pt;
	font-weight:bold;
	margin:0.5em;
	letter-spacing:2pt;
}
h1{
	text-align:center;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 18pt;
	font-weight: bold;
}
.kicsi8pt{
	font-size:8pt;
}
.kicsi9pt{
	font-size:9pt;
}
.szellos{
	line-height:18pt;
	text-align:left;
}
.szellos_kicsi{
	line-height:15pt;
}
.vekony_keret {
    border:1px solid #000000;
    border-collapse:collapse;
}
</style>
</head>
<body>' . $tartalom . '</body>
</html>';

        if (!is_dir(BORDER_PATH_BORDER . 'mod/pd4ml')) mkdir(BORDER_PATH_BORDER . 'mod/pd4ml');
        $htmlfile = BORDER_PATH_BORDER . 'mod/pd4ml/pre' . session_id() . config('user_id') . uniqid() . '.html';
        @unlink($htmlfile);
        if (!$fhtml = fopen($htmlfile, 'w')) die('Error open file: ' . $htmlfile);
        if (!fwrite($fhtml, $tartalom)) die('Error write file: ' . $htmlfile);
        fclose($fhtml);

        if ($fejparam['papir_meret'] == 'A4') {
            // A4
            $width = $fejparam['allo'] == 'PORTRAIT' ? 794 : 1123;
        } else {
            //A5
            $width = $fejparam['allo'] == 'PORTRAIT' ? 559 : 794;
        }

        $parancs = (defined('BORDER_PATH_JAVA') ? BORDER_PATH_JAVA : '/opt/jdk8/bin/java') .
            ' -Xmx512m -Djava.awt.headless=true -Dfile.encoding=UTF-8 -cp ' .
            BORDER_PATH_BORDERLIB . 'pd4ml/pd4ml.jar Pd4Cmd "file:' . $htmlfile . '" ' .
            $width . ' ' . $fejparam['papir_meret'] . ' -orientation ' .
            $fejparam['allo'] . ' -insets ' .
            $fejparam['margo_fent'] . ',' .
            $fejparam['margo_bal'] . ',' .
            $fejparam['margo_lent'] . ',' .
            $fejparam['margo_jobb'] . ',mm ' .
            '-smarttablesplit ' . '-ttf ' . BORDER_PATH_BORDERLIB . 'pd4ml/fonts';

        // if ($defult_param['vizjel_aktiv'] == 1 && !empty($defult_param['vizjel_path'])) {
        //     $parancs .= ' -bgimage \'file:' . $defult_param['vizjel_path'] . '\'';
        // }


        if (substr(php_uname(), 0, 7) == "Windows") {
            //windows alatt levágja a bináris állományt, ezért fájlba mentjük
            shell_exec($parancs . ' > \tmp.pdf');
            $pdfkimenet = file_get_contents('\tmp.pdf');
            unlink('\tmp.pdf');
            // exec($parancs, $pdfkimenet);
            // $pdfkimenet = implode("\n", $pdfkimenet);
        } else {
            $pdfkimenet = shell_exec($parancs);
        }

        if (!unlink($htmlfile)) die('Error delete file: ' . $htmlfile);

        // dd($pdfkimenet, $parancs);
        return $pdfkimenet;
    }
}
