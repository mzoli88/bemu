<?php

namespace mod\admin\commands;

use App\Exceptions\SendErrorException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use mod\admin\models\Users;
use hws\CacheQueue;
use Throwable;

$Callq_file = null;
class Callq extends Command
{

    protected $signature = 'callq {method} {route} {user_id} {entity_id} {import_file_path?} {file_name?}';
    // protected $signature = 'callq {method} {route}';


    protected $description = 'Console-ba futtatható controller';


    public function handle(): void
    {
        Config::set('callq', true);

        $method = $this->argument('method');
        $route = urldecode(base64_decode($this->argument('route')));
        $user_id = $this->argument('user_id');
        $entity_id = $this->argument('entity_id');
        $import_file_path = base64_decode($this->argument('import_file_path'));
        $file_name = base64_decode($this->argument('file_name'));

        global $global_modul_azon;
        $global_modul_azon = preg_replace(['#^\/#', '/\/.*$/'], '', $route);

        parse_str(preg_replace('/.*\?/', '', $route), $_GET);

        $_GET['entity_id'] = $entity_id;

        if ($user_id) {
            global $global_active_user;
            $user = Users::findOne($user_id);
            if ($user) $global_active_user = $user;
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

        $has_err = false;

        $content = null;
        $request = Request::create($route, $method, $_GET);
        app()->instance('request', $request);
        logger()->debug("workQuequeRun start: " . getmypid());
        try {
            $content = app()->handle($request);

            if ($content->exception instanceof Throwable) $has_err = 'Hiba történt';

            if ($content->exception instanceof SendErrorException) $has_err = $content->exception->getMessage();

            if ($content instanceof \Illuminate\Http\Response || $content instanceof \Illuminate\Http\JsonResponse) {
                $content = $content->getOriginalContent();
            }
        } catch (\Throwable $th) {
            $content = $th->getMessage();
            $has_err = 'Hiba történt';
            report($th);
        }

        global $Callq_file; // így nem kell return az import/export esetén
        if($Callq_file) $content = $Callq_file;

        if(config('error')) $has_err = config('error');

        if (!empty($import_file_path) && file_exists($import_file_path)) {
            unlink($import_file_path);
        }

        $cache = CacheQueue::getCache();

        if (!$cache) {
            sleep(2);
            $cache = CacheQueue::getCache();
        }

        if (!$cache) self::stop($content);

        if ($cache["pid"] != getmypid()) self::stop($content);

        $cache["ready"] = true;
        $cache["content"] = $content;
        $cache["has_err"] = $has_err;
        logger()->debug("workQuequeRun stop: " . getmypid() . "\n" . print_r($cache, true));

        CacheQueue::setCache($cache);
    }

    static function stop($ct)
    {
        if (is_array($ct) && array_key_exists('download', $ct) && $ct['download'] == true) {
            Storage::delete($ct['file']);
        }
        exit;
    }
}
