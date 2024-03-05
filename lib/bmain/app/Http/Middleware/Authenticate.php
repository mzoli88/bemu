<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;
class Authenticate
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!getUser()) sendError("Nincs belépve!", 401); 
        return $next($request);
    }
}
