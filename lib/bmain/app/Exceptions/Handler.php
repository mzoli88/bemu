<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
        if ($exception instanceof ValidationException) {
            logger()->debug('ValidationException: ' . print_r($exception->errors(), true));
            return;
        }
        if ($exception instanceof SendErrorException) {
            logger()->debug('SendErrorException: ' . $exception->getTitle() . "\n" . $exception->getMessage());
            return;
        }
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

        if ($exception instanceof SendErrorException) {
            return response()->json($exception->getErrorMessage(), $exception->getCode());
        }

        if ($exception instanceof AuthenticationException) {
            return sendError('Helytelen authentikáció', 401);
        }

        if ($exception instanceof ThrottleRequestsException) {
            return sendError('Túl sok kérés.', 429);
        }

        //Szabványos hibakezelés ha debug mód be van kapcsolva
        if (defined('BORDER_EMU')) config(['app.debug' => true]);
        if (config('app.debug')) return parent::render($request, $exception);

        //Alapértelmezett hibaüzenet
        return sendError('Szerver oldalon hiba történt', 500);
    }
}
