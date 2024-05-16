<?php

namespace mod\admin\commands;

use hws\CacheQueue;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use mod\admin\models\Nevek;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class Callq extends Command
{

    protected $signature = 'callq {method} {route} {user_id} {entity_id} {import_file_path?} {file_name?}';


    protected $description = 'Console-ba futtatható controller';


    public function handle(): void
    {

        Config::set('callq', true);

        $method = $this->argument('method');
        $route = urldecode(base64_decode($this->argument('route')));
        $user_id = $this->argument('user_id');
        $entity_id = $this->argument('entity_id');
        $import_file_path = $this->argument('import_file_path');
        $file_name = $this->argument('file_name');

        global $global_modul_azon;
        $global_modul_azon = preg_replace(['#^\/#', '/\/.*$/'], '', $route);

        parse_str(preg_replace('/.*\?/', '', $route), $_GET);

        $_GET['entity_id'] = $entity_id;

        if ($user_id) {
            global $global_active_user;
            $user = Nevek::findOne($user_id);
            if ($user) {
                $global_active_user = (object)[
                    'id' => $user->id,
                    'login' => $user->nev,
                    'name' => $user->teljesnev,
                ];
            }
        }

        $cache = CacheQueue::getCache();
        $cache["pid"] = getmypid();
        CacheQueue::setCache($cache);

        if (!empty($import_file_path)) {
            $_FILES = [
                'import_file' => [
                    'tmp_name' => $import_file_path,
                    'name' => $file_name
                ]
            ];
        }
        
        logger()->info($_FILES);
        $content = null;
        $request = Request::create($route, $method,$_GET);
        app()->instance('request',$request);
        logger()->debug("workQuequeRun start: ".getmypid());
        try {
            $content = app()->handle($request);
            if ($content instanceof \Illuminate\Http\Response || $content instanceof \Illuminate\Http\JsonResponse) {
                $content = $content->getOriginalContent();
            }
        } catch (\Throwable $th) {
            $content = $th->getMessage();
            report($th);
        }

        logger()->debug("workQuequeRun stop: " . getmypid() . print_r($content, true));
        
        if (!empty($import_file_path)) {
            unlink($import_file_path);
        }

        $cache = CacheQueue::getCache();

        if(!$cache) self::stop($content);
        if($cache["pid"] != getmypid()) self::stop($content);

        $cache["ready"] = true;
        $cache["content"] = $content;
        CacheQueue::setCache($cache);
        
    }

    static function stop($ct){
        if(is_array($ct) && array_key_exists('download',$ct) && $ct['download'] == true){
            Storage::delete($ct['file']);
        }
        exit;
    }
}
