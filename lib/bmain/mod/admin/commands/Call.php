<?php

namespace mod\admin\commands;

use Illuminate\Http\Request;
use Illuminate\Console\Command;
use mod\admin\models\Nevek;

class Call extends Command
{

    protected $signature = 'call {method} {route} {user_id?}';


    protected $description = 'Console-ba futtatható controller';


    public function handle(): void
    {

        $method = $this->argument('method');
        $route = $this->argument('route');
        $user_id = $this->argument('user_id');

        parse_str(preg_replace('/.*\?/', '', $route), $_GET);

        if($user_id){
            global $global_active_user;
            $user = Nevek::findOne($user_id);
            if($user){
                $global_active_user = (object)[
                    'id' => $user->id,
                    'login' => $user->nev,
                    'name' => $user->teljesnev,
                ];
            }
        }

        $request = Request::create($route, $method);
        $response = app()->handle($request);

        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\JsonResponse) {
            dd($response->getOriginalContent());
        } else {
            dd($response);
        }
    }
}
