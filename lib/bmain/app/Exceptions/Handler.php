<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use \Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        //validálás hiba json kiirítás
        if ($exception instanceof ValidationException) {
            return response()->json($exception->errors(), 422);
        }

        //Szabványos hibakezelés ha debug mód be van kapcsolva
        if (config('app.debug')) return parent::render($request, $exception);
        //Alapértelmezett hibaüzenet
        // Json adat visszadása
        return response()->json([
            'message' => $exception->getMessage(),
        ], 400);
    }
}
