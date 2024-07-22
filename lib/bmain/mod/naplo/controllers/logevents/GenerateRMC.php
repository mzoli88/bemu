<?php

namespace mod\naplo\controllers\logevents;

use hws\rmc\Controller3;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenerateRMC extends Controller3
{

    public function create(Request $request)
    {
        $modul_azon = $request->modul_azon;
        return response()->streamDownload(function () use ($modul_azon) {

            echo "\xEF\xBB\xBF";
            $out = [];
            collect($this->find_all_files(base_path('mod' . DIRECTORY_SEPARATOR . $modul_azon)))->filter(function ($r) {
                return strtolower(pathinfo($r, PATHINFO_EXTENSION)) == 'php';
            })->each(function ($r) use ($modul_azon, &$out) {

                $tmp = (explode('mod' . DIRECTORY_SEPARATOR . $modul_azon, $r));
                array_shift($tmp);
                $tmp = array_shift($tmp);
                $class = str_replace('/', '\\', 'mod' . DIRECTORY_SEPARATOR . $modul_azon . str_replace('.php', '', $tmp));
                if (class_exists($class)) {

                    $object = new \ReflectionClass($class);

                    if ($object->hasProperty('log_event_id')) {
                        $prop = $object->getProperty('log_event_id');
                        $prop->setAccessible(true);

                        $event_id = null;

                        try {
                            $event_id = $prop->getValue(new $class);
                        } catch (\Throwable $th) {
                        }

                        if (!empty($event_id)) {
                            if ($object->hasMethod('list')) $out[] = $event_id . ' - lista';
                            if ($object->hasMethod('view')) $out[] = $event_id . ' - részletek';
                            if ($object->hasMethod('create')) $out[] = $event_id . ' - létrehozás';
                            if ($object->hasMethod('update')) $out[] = $event_id . ' - módosítás';
                            if ($object->hasMethod('delete')) $out[] = $event_id . ' - törlés';
                            if ($object->hasMethod('export')) $out[] = $event_id . ' - exportálás';
                            if ($object->hasMethod('import')) $out[] = $event_id . ' - import';
                            if ($object->hasMethod('download')) $out[] = $event_id . ' - letöltés';
                        }
                    }
                }

                $file_ct = file_get_contents($r);

                $tmp = (preg_split('#hwslog#', $file_ct));
                array_shift($tmp);

                if (empty($tmp)) return;

                collect($tmp)->each(function ($val) use (&$out) {
                    $len = strpos($val, ');');

                    $has = substr($val, 0, $len);
                    if (empty($has)) return;

                    $has = str_replace(["\n", "\r"], '', $has);
                    $has = preg_replace('/\[.*\]/', '', $has);

                    if (preg_match('#event\:#', $has)) {
                        $tmp2 = explode('event:', $has);
                        array_shift($tmp2);
                        $has = array_shift($tmp2);
                        $tmp2 = explode(',', $has);
                        $has = array_shift($tmp2);
                    }else{

                        $tmp2 = explode(',', $has);
    
                        array_shift($tmp2);
                        $has = array_shift($tmp2);
                    }

                    // dd($has);
                    if (!empty($has)) $out[] = trim(str_replace(['\'', '"', 'message:'], '', $has));
                });
            });

            collect($out)->each(function ($val) {
                echo $val . "\n";
            });
        }, basename($modul_azon . '_db_' . date('YmdHis') . '.csv'));
    }


    public function find_all_files($dir)
    {

        $root = scandir($dir);
        $result = [];

        foreach ($root as $value) {

            if ($value === '.' || $value === '..') continue;
            if ($value == '.git') continue;
            if ($value == 'schedule.php') continue;
            if ($value == 'config.php') continue;

            if (is_file($dir . DIRECTORY_SEPARATOR . $value)) {
                $result[] = $dir . DIRECTORY_SEPARATOR . $value;
                continue;
            }


            foreach ($this->find_all_files("$dir/$value") as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }
}
