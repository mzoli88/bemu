<?php

use App\Border3;
use hws\rmc\Model;
use App\Exceptions\SendErrorException;

function getUser()
{

    if(config('user_cached','nono') == 'nono') {
        if (empty(session_id())) {
            session_name('SESS_' . config('BORDER_PREFIX') . 'ID');
            session_start();
        }
    
        if (empty($_SESSION['id'])){
            config(['user_cached' => false]);
        }else{
            config(['user_cached' => (object)[
                'id' => (int)$_SESSION['id'],
                'login' => toUtf($_SESSION['nev']),
                'name' => toUtf($_SESSION['teljesnev']),
            ]]);
        }
    }
    return config('user_cached');
}

function getUserId()
{
    $user = getUser();
    return $user ? $user->id : null;
}

function getModulAzon()
{
    $modul_azon = null;
    if (request()->route()) $modul_azon = preg_replace('/\/.*$/', '', request()->route()->getPrefix());
    if (app()->runningInConsole()) {
        $trace = collect(debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 10))->slice(1)->pluck('class');
        $find = $trace->filter(function ($v) {
            return preg_match('#^mod\\.*#', $v);
        })->first();
        if ($find) {
            $tmp = explode('\\', $find);
            $modul_azon = $tmp[1];
        }
    }
    if ($modul_azon == 'auth' || $modul_azon == 'start' || empty($modul_azon)) $modul_azon = 'admin';

    return $modul_azon;
}

function isSysAdmin()
{
    return Border3::rendszergazda_e();
}

// function getMenu()
// {
//     $route = explode('/', request()->route()->getPrefix());
//     if (count($route) < 2) return false;
//     return $route[1];
// }

function getEntity()
{
    $request = request();
    return intval($request->header('Active-Entity') ?: $request->header('Entity-Id') ?: $request->get('entity_id'));
}

// function getEntityName($entity_id = null)
// {
//     $entity_id = $entity_id ?: getEntity();
//     if (!$entity_id) return;
//     $entities = entities();
//     return array_key_exists($entity_id, $entities) ? $entities[$entity_id]['name'] : null;
// }

// function entities()
// {
//     return Cache::remember('entities', now()->addMinutes(10), function () {
//         return Entities::status()->get()->keyBy('id')->toArray();
//     });
// }

function hasJustOneEntity()
{
    // return count(entities()) == 1;
    return true;
}

// function getUserPerms($user_id = null)
// {
//     $user_id = $user_id ?: getUserId();
//     return Cache::remember('user_perms_' . $user_id, now()->addMinutes(10), function () use ($user_id) {
//         $modul_azons = array_keys(config('mods'));
//         $entities = getUserEntities($user_id);
//         $UserPerms = UserPerms::where('user_id', $user_id)->whereIn('modul_azon', $modul_azons)->whereIn('entity_id', $entities)->get()->toArray();
//         $GroupPerms = GroupPerms::whereIn('group_id', UserGroups::where('user_id', $user_id)->join('Rel_group')->where('Rel_group.status', 'I')->get()->pluck('group_id'))->whereIn('entity_id', $entities)->get()->toArray();
//         // $isSysAdmin = Users::findOne($user_id)->sys_admin == 'I';
//         $mods = config('mods');
//         return collect($UserPerms)->merge($GroupPerms)->groupBy(['entity_id', 'modul_azon', 'perm'])->map(function ($r) use ($mods) {
//             return $r->map(function ($r, $modul_azon) use ($mods) {
//                 return $r->map(function () {
//                     return true;
//                 })->filter(function ($b, $jog) use ($modul_azon, $mods) {
//                     if (!array_key_exists($modul_azon, $mods)) return false;
//                     if (array_key_exists('menu', $mods[$modul_azon]) && array_key_exists($jog, $mods[$modul_azon]['menu'])) return true;
//                     if (array_key_exists('perms', $mods[$modul_azon]) && array_key_exists($jog, $mods[$modul_azon]['perms'])) return true;
//                     return false;
//                 });
//             })->filter(function ($jogok, $modul_azon) use ($mods) {
//                 //ellenőrizzük, hogy ha csak elemi joga van akkor ne legyen joga, mert nem láthatja a menüpontokat
//                 if (!array_key_exists($modul_azon, $mods)) return false;
//                 if (!array_key_exists('menu', $mods[$modul_azon])) return false;
//                 $menus = collect($mods[$modul_azon]['menu'])->keys()->toArray();
//                 $hasJogs = $jogok->filter(function ($v) {
//                     return $v;
//                 })->filter(function ($v, $k) use ($menus) {
//                     return in_array($k, $menus);
//                 })->keys()->toArray();
//                 return !empty($hasJogs);
//             });
//         })->toArray();
//     });
// }

// function getUserEntities($user_id = null)
// {
//     $user_id = $user_id ?: getUserId();
//     return Cache::remember('user_entities_' . $user_id, now()->addMinutes(10), function () use ($user_id) {
//         return UserEntities::where('user_id', $user_id)->pluck('entity_id')->toArray();
//     });
// }

// function getUserPermsByEntity($user_id = null, $entity_id = null)
// {
//     $user_id = $user_id ?: getUserId();
//     $entity_id = $entity_id ?: getEntity();
//     $perms = getUserPerms($user_id);
//     if (array_key_exists($entity_id, $perms)) return $perms[$entity_id];
//     return false;
// }

// function hasEntity($user_id = null, $entity_id = null)
// {
//     $user_id = $user_id ?: getUserId();
//     $entity_id = $entity_id ?: getEntity();
//     $entities = getUserEntities($user_id);
//     return in_array($entity_id, $entities);
// }

function hasPerm($jog)
{
    if ($jog === false || !call_user_func_array([Border3::class, 'getJog'], func_get_args())) return false;
    return true;
}

// function setParams(array $params, $modul_azon = null)
// {
//     $modul_azon = $modul_azon ?: getModulAzon();
//     collect($params)->each(function ($value, $key) use ($modul_azon) {
//         $rec = Params::where('modul_azon', $modul_azon)->where('key', $key)->one();

//         if ($rec) {
//             $rec->value = $value;
//             $rec->save();
//         } else {
//             Params::create([
//                 'modul_azon' => $modul_azon,
//                 'key' => $key,
//                 'value' => $value,
//             ]);
//         }
//     });
//     Params::cache();
// }

// function getParams($modul_azon = null)
// {
//     $modul_azon = $modul_azon ?: getModulAzon();
//     return collect(Params::getCache())
//         ->filter(function ($v, $k) use ($modul_azon) {
//             return preg_match('/^' . $modul_azon . '\//', $k);
//         })
//         ->mapWithKeys(function ($v, $k) {
//             return [preg_replace('/^.*\//', '', $k) => $v];
//         })
//         ->toArray();
// }

// function getParam($key, $modul_azon = null)
// {
//     $params = getParams($modul_azon);
//     return array_key_exists($key, $params) ? $params[$key] : null;
// }

function toHtml($text)
{
    return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
}

function sendError($text, $code = 400, $title = null)
{
    throw new SendErrorException($text, $code, $title);
}

function hwslog($log_chanels = null, String $event = null, String $message = null, String $err = null, $entity_id = null, $modul_azon = null)
{
    $entity_id = $entity_id ?: getEntity();
    $modul_azon = $modul_azon ?: getModulAzon();

    if (app()->runningInConsole()) {
        $log_chanels = ['audit', 'system'];
    } else {
        if (!$log_chanels) {
            $log_chanels = config('default_log_chanels', ['audit', 'security']);
            $route = request()->route();
            if ($route) {
                $routeArray = explode('/', $route->uri);
                if ($routeArray[1] == 'interfaces') {
                    $log_chanels = ['audit', 'system'];
                }
            }
        }
    }

    if (empty($event)) {
        $event = '';
        $route = request()->route();
        if ($route) {
            $controller = $route->getController();
            if (!empty($controller) && ($controller->log_event_id || !empty($controller->log_event_action))) {
                $event = $controller->log_event_id;
                if ($controller->log_event_action && array_key_exists($controller->action, $controller->log_event_action)) {
                    if (!empty($controller->log_event_action[$controller->action])) {
                        if ($event) {
                            $event .= ' - ';
                        }
                        $event .= $controller->log_event_action[$controller->action];
                    }
                } else {
                    switch ($controller->action) {
                        case 'list':
                            $event .= ' - lista';
                            break;
                        case 'view':
                            $event .= ' - részletek';
                            break;
                        case 'create':
                            $event .= ' - létrehozás';
                            break;
                        case 'update':
                            $event .= ' - módosítás';
                            break;
                        case 'delete':
                            $event .= ' - törlés';
                            break;
                        case 'export':
                            $event .= ' - exportálás';
                            break;
                        case 'import':
                            $event .= ' - import';
                            break;
                        case 'download':
                            $event .= ' - letöltés';
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }

    if (empty($message)) {
        $message = '';
        if (!empty($_GET)) {
            $message .= "Query paraméterek:\n";
            $message .= collect($_GET)->map(function ($val, $key) {
                return "\t" . $key . ' = ' . $val;
            })->implode("\n");
        }

        $inputLog = false;
        if (!empty($_POST)) {
            $inputLog = json_encode($_POST);
        } else {
            $input = file_get_contents("php://input");
            if (!empty($input)) $inputLog = $input;
        }

        if ($inputLog && !empty($inputLog)) {
            $message .= (empty($message) ? '' : "\n\n") . "INPUT paraméterek:\n" . $inputLog;
        }

        if (!empty(Model::$searchLog)) {
            $message .= (empty($message) ? '' : "\n\n") . "Keresés:\n";
            $message .= collect(Model::$searchLog)->map(function ($rec) {
                if (!is_string($rec[2]) && !is_numeric($rec[2]) & !is_double($rec[2])) $rec[2] = preg_replace('/\s+/', ' ', str_replace(["\n", "\t"], ' ', var_export($rec[2], true)));
                return "\t" . $rec[0] . ' ' . $rec[1] . ' ' . $rec[2];
            })->implode("\n");
        }
        if (!empty(Model::$modifyLog)) {
            $message .= (empty($message) ? '' : "\n\n") . collect(Model::$modifyLog)->implode("\n\n");
        }
    }
    // dd (app('log') ,$log_chanels,$message,$event);
    if ($event) {
        collect($log_chanels)->each(function ($channel) use ($message, $event, $err, $entity_id, $modul_azon) {
            $context = [
                'event' => $event,
                'code' => $err ? 'Sikertelen' : 'Sikeres',
                'error' => $err,
                'entity_id' => $entity_id,
                'channel' => $channel
            ];
            if ($modul_azon) $context['modul_azon'] = $modul_azon;
            logger()->info($message, $context);
        });
    }
}

function toUtf($str)
{
    if (!mb_check_encoding($str, 'UTF-8')) $str = iconv('ISO-8859-2', 'UTF-8', $str);
    return str_replace(['õ', 'Õ', 'û', 'Û'], ['ő', 'Ő', 'ű', 'Ű'], $str);
}

function conv($str)
{
    return str_replace(['ő', 'Ő', 'ű', 'Ű'], ['õ', 'Õ', 'û', 'Û'], $str);
}

function invertConv($str)
{
    return str_replace(['õ', 'Õ', 'û', 'Û'], ['ő', 'Ő', 'ű', 'Ű'], $str);
}

function toLatin($str)
{
    return conv(invertConv($str));
}