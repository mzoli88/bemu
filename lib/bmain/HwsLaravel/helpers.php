<?php

use hws\Border;
use hws\Border2;
use hws\rmc\Model;
use PHPMailer\PHPMailer\PHPMailer;

function sendError($text,$code = 400,$title=null)
{
    $out = [
        'message' => $text,
    ];
    if($title)$out['title'] = $title;
    response()->json($out, $code)->send();
    exit();
}

function getUserId()
{
    return config('user_id');
}

function getModulAzon()
{
    return config('modul_azon');
}

function hasPerm($jog)
{
    if ($jog === false || !call_user_func_array([Border2::class, 'getJog'], func_get_args())) {
        return false;
    }
    return true;
}

function hasJustOneEntity()
{
    return !config('use_entity');
}

function getEntity()
{
    if(class_exists('hws\Border2')){
        return Border2::getActiveEntity();
    }else{
        return Border::getActiveEntity();
    }
}

function conv($str)
{
    return str_replace(['ő', 'Ő', 'ű', 'Ű'], ['õ', 'Õ', 'û', 'Û'], $str);
}

function invertConv($str)
{
    return str_replace(['õ', 'Õ', 'û', 'Û'], ['ő', 'Ő', 'ű', 'Ű'], $str);
}

function toUtf($str)
{
    if (!mb_check_encoding($str, 'UTF-8')) $str = iconv('ISO-8859-2', 'UTF-8', $str);
    return invertConv($str);
}

function toLatin($str)
{
    return conv(invertConv($str));
}

function write($path, $data)
{
    file_put_contents(fkdir($path), "<?php\nreturn " . var_export($data, true) . ";");
}

function str_in($find, $search)
{
    if (strpos(mb_strtolower($search), mb_strtolower($find)) !== false) {
        return true;
    } else {
        return false;
    }
}

function fcache($path, $data = null)
{
    $path = spath($path);
    if (empty($data)) {
        return read($path);
    } else {
        write($path, $data);
    }
}

function read($path)
{
    $path = realpath($path);
    if ($path) {
        return @include($path);
    } else {
        return [];
    }
}

function fkdir($path)
{
    $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    $tmp_exp = explode(DIRECTORY_SEPARATOR, $path);
    array_pop($tmp_exp);
    $tmp_string = array_shift($tmp_exp);
    if (!empty($tmp_exp)) {
        foreach ($tmp_exp as $next) {
            $tmp_string .= DIRECTORY_SEPARATOR . $next;
            if (!is_dir($tmp_string)) mkdir($tmp_string);
        }
        // dd($tmp_string);
    }
    return $path;
}

function spath($path)
{
    $dir = config('filesystems.disks.local.root');
    return fkdir($dir . DIRECTORY_SEPARATOR . $path);
}

function fcsv($path, array $row, $new = false, $bom = true)
{
    if ($new && $bom) {
        $st = "\xEF\xBB\xBF";
    } else {
        $st = '';
    }
    foreach ($row as $col) {
        $val = str_replace([";", "\n"], [',', ''], trim($col));
        $st .= '"' . $val . '";';
    }
    file_put_contents($path, $st . "\n", $new ? null : FILE_APPEND);
}


function hwslog($log_chanels = null, String $event = null, String $message = null, String $err = null)
{


    if (app()->runningInConsole()) {
        $log_chanels = ['audit', 'system'];
    } else {
        $log_chanels = $log_chanels ?: config('default_log_chanels', ['audit', 'security']);
    }

    if (empty($event)) {
        $event = '';
        $route = request()->route();
        if ($route) {
            $controller = $route->getController();
            if (!empty($controller) && $controller->log_event_id) {
                $event = $controller->log_event_id;

                if ($controller->log_event_action && array_key_exists($controller->action, $controller->log_event_action)) {
                    if (!empty($controller->log_event_action[$controller->action])) $event .= ' - ' . $controller->log_event_action[$controller->action];
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

        if (!empty(Model::$inputLog)) {
            $message .= (empty($message) ? '' : "\n\n") . "INPUT paraméterek:\n" . (Model::$inputLog);
        }

        if (!empty(Model::$searchLog)) {
            $message .= (empty($message) ? '' : "\n\n") . "Keresés:\n";
            $message .= collect(Model::$searchLog)->map(function ($rec) {
                if (!is_string($rec[2]) && !is_numeric($rec[2]) && !is_double($rec[2])) $rec[2] = preg_replace('/\s+/', ' ', str_replace(["\n", "\t"], ' ', var_export($rec[2], true)));
                return "\t" . $rec[0] . ' ' . $rec[1] . ' ' . $rec[2];
            })->implode("\n");
        }
        if (!empty(Model::$modifyLog)) {
            $message .= (empty($message) ? '' : "\n\n") . collect(Model::$modifyLog)->implode("\n\n");
        }
    }

    collect($log_chanels)->each(function ($channel) use ($message, $event, $err) {
        logger()->info($message, [
            'event' => $event,
            'code' => $err ? 'Sikertelen' : 'Sikeres',
            'error' => $err,
            'channel' => $channel
        ]);
    });
}

/*
    Sablon helyettesítő helper
*/
function helyettesit($szoveg, $array, $del_left = true)
{
    foreach ($array as $key => $value) {
        $szoveg = str_replace('{' . $key . '}', $value, $szoveg);
    }

    if ($del_left) return preg_replace('/\{.*\}/', '', $szoveg);
    return $szoveg;
}

/*
    $params['Host']
    $params['Hostname']
    $params['SMTPAuth']
    $params['SMTPAutoTLS']
    $params['Username']
    $params['Password']
    $params['SMTPSecure']
    $params['Port']
*/
function sendEmail($cimzett, $targy, $msg, $felado = null, $Attachment = null, $IsHTML = true, $smtp = false, $params = [], $Embeded = [])
{

    if ($IsHTML) $msg = nl2br($msg);

    // PHPMailer
    $mailer = new PHPMailer();

    if ($smtp == true) {
        $mailer->isSMTP();
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') $mailer->{$key} = $value;
        }
    }

    $mailer->SetLanguage('hu');
    $mailer->CharSet  = 'utf-8';

    $mailer->setFrom($felado ?: 'noreply@noreply.cloud.hwstudio.hu');

    if (is_array($cimzett)) {
        foreach ($cimzett as $key => $value) {
            $mailer->AddAddress(trim($value));
        }
    } else {
        $mailer->AddAddress(trim($cimzett));
    }

    if (!empty($Attachment)) {
        foreach ($Attachment as $value) {
            // [filecontent, fileName]
            $mailer->AddStringAttachment($value['0'], $value['1']);
        }
    }

    if (!empty($Embeded)) {
        foreach ($Embeded as $value) {
            // [filecontent, cid,filename]
            $mailer->addStringEmbeddedImage($value['0'], $value['1'], $value['2']);
        }
    }

    $mailer->Subject  = $targy;
    $mailer->IsHTML($IsHTML);
    $mailer->Body     = $msg;

    logger()->info('E-mail küldés paraméterek: ' . print_r($mailer, true));
    if (defined('BORDER_EMU')) return true;

    if (!$mailer->Send()) {
        logger()->error($mailer->ErrorInfo . print_r([
            'cimzett' => $cimzett,
            'felado' => $felado,
            'targy' => $targy,
            'Body' => $mailer->Body,
        ], true));
    } else {
        logger()->info("sikeres email küldés\n" . print_r([
            'cimzett' => $cimzett,
            'felado' => $felado,
            'targy' => $targy,
            'Body' => $mailer->Body,
        ], true));
        return true;
    }
}
