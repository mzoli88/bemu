<?php

namespace App\Providers;

use App\Routers\ApiRouter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        
        $this->routes(function () {
            //controllers
            collect(config('mods'))->each(function ($data, $modul_azon) {
                Route::middleware(['api','auth'])->prefix($modul_azon)->group(function () use ($data) {
                    ApiRouter::route($data['dir'] . '/controllers');
                });
                Route::prefix($modul_azon . '/interfaces')->group(function () use ($data) {
                    ApiRouter::route($data['dir'] . '/interfaces','IF');
                });
            });
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            return Limit::perMinute(60)->by(($user ? $user->id : $request->ip()) . '|' . $request->route()->uri());
        });
    }
}
