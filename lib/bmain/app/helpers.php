<?php

use App\Border3;
use hws\rmc\Model;
use App\Exceptions\SendErrorException;
use mod\admin\models\Params;

//globális változók cache miatt
$global_modul_azon = null;
$global_active_user = null;

function getUser()
{
    global $global_active_user;

    if ($global_active_user != null) return $global_active_user;

    if (app()->runningInConsole()) {
        $global_active_user = false;
        return false;
    }

    if (!isset($_COOKIE['SESS_' . BORDER_PREFIX . 'ID'])) {
        $global_active_user = false;
        return false;
    }

    if (empty(session_id())) {
        session_name('SESS_' . BORDER_PREFIX . 'ID');
        session_start();
    }

    if (empty($_SESSION['id'])) {
        $global_active_user = false;
        return false;
    } else {
        $global_active_user = (object)[
            'id' => (int)$_SESSION['id'],
            'login' => toUtf($_SESSION['nev']),
            'name' => toUtf($_SESSION['teljesnev']),
        ];
        return $global_active_user;
    }
}

function getUserId()
{
    $user = getUser();
    return $user ? $user->id : null;
}

function getModulAzon()
{
    global $global_modul_azon;

    if ($global_modul_azon) return $global_modul_azon;

    if (request()->getPathInfo()) {
        $global_modul_azon = preg_replace(['#^\/#', '/\/.*$/'], '', request()->getPathInfo());
    } else if (request()->route()) {
        $global_modul_azon = preg_replace('/\/.*$/', '', request()->route()->getPrefix());
    }
    if ($global_modul_azon == 'auth' || $global_modul_azon == 'start' || empty($global_modul_azon)) $global_modul_azon = 'admin';

    return $global_modul_azon;
}

function isSysAdmin()
{
    return Border3::rendszergazda_e();
}

function getModulName($modul_azon = null)
{
    $modul_azon = $modul_azon ?: getModulAzon();
    $mods = config('mods');
    if (!array_key_exists($modul_azon, $mods)) return '';
    if (!array_key_exists('name', $mods[$modul_azon])) return '';
    return $mods[$modul_azon]['name'];
}

function getModulVersion($modul_azon = null)
{
    $modul_azon = $modul_azon ?: getModulAzon();
    $mods = config('mods');
    return $mods[$modul_azon]['version'];
}

function getMenu()
{
    $route = explode('/', request()->route()->getPrefix());
    if (count($route) < 2) return false;
    if ($route[1] == 'interfaces') return false;
    return $route[1];
}

function getEntity()
{
    $request = request();
    return intval($request->header('Active-Entity') ?: $request->header('Entity-Id') ?: $request->get('entity_id'));
}

function getEntityName($entity_id = null)
{
    $entity_id = $entity_id ?: getEntity();
    if (!$entity_id) return;
    $entities = entities();
    return array_key_exists($entity_id, $entities) ? $entities[$entity_id]['name'] : null;
}

function entities()
{
    return Border3::getEntities();
}

function hasJustOneEntity()
{
    return count(entities()) == 1;
}

function getUserEntities($user_id = null)
{
    return Border3::getUserEntityIds($user_id);
}

function hasEntity($user_id = null, $entity_id = null)
{
    $user_id = $user_id ?: getUserId();
    $entity_id = $entity_id ?: getEntity();
    $entities = getUserEntities($user_id);
    return in_array($entity_id, $entities);
}

function hasPerm($jog)
{
    if ($jog === true) return true;
    if ($jog === false || !call_user_func_array([Border3::class, 'getJog'], func_get_args())) return false;
    return true;
}

function setParams(array $params, $modul_azon = null)
{
    $modul_azon = $modul_azon ?: getModulAzon();
    collect($params)->each(function ($value, $key) use ($modul_azon) {
        $rec = Params::where('modul_azon', $modul_azon)->where('key', $key)->one();

        if ($rec) {
            $rec->value = $value;
            $rec->save();
        } else {
            Params::create([
                'modul_azon' => $modul_azon,
                'key' => $key,
                'value' => $value,
            ]);
        }
    });
    Params::cache();
}

function getParams($modul_azon = null)
{
    $modul_azon = $modul_azon ?: getModulAzon();
    return collect(Params::getCache())
        ->filter(function ($v, $k) use ($modul_azon) {
            return preg_match('/^' . $modul_azon . '\//', $k);
        })
        ->mapWithKeys(function ($v, $k) {
            return [preg_replace('/^.*\//', '', $k) => $v];
        })
        ->toArray();
}

function getParam($key, $modul_azon = null)
{
    $params = getParams($modul_azon);
    return array_key_exists($key, $params) ? $params[$key] : null;
}

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
