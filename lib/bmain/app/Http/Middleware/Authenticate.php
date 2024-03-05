<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;
class Authenticate
{

    public function handle(Request $request, Closure $next): Response
    {
        if (empty(session_id())) {
            session_name('SESS_' . config('BORDER_PREFIX') . 'ID');
            session_start();
        }
        
        if (empty($_SESSION['id'])) sendError("Nincs belépve!", 401);
 
        return $next($request);
    }
}
