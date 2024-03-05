<?php

namespace App\Http\Middleware;

use App\AuthController;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Str;

class Authenticate extends Middleware
{

    protected function authenticate($request, array $guards)
    {

        $out = parent::authenticate($request, $guards);



        if (getParam('maintenance_mode','admin') == 'I' && !isSysAdmin()) {
            sendError(getParam('maintance_text','admin'),503,getParam('maintance_header','admin'));
        }

        return $out;
    }

    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            // return route('auth/logout');
            // return response()->json([]);
            return null;
        }
    }
}
