<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof ModelNotFoundException)
                return response()->json(['status' => false, 'errors' => 'Model Not Found'], 404);

            if ($e instanceof NotFoundHttpException)
                return response()->json(['status' => false, 'errors' => 'Invalid Route'], 404);

            if ($e instanceof ThrottleRequestsException)
                return response()->json(['status' => false, 'errors' => 'Too many requests'], 429);
        }
        return parent::render($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson())
            return response()->json(['status' => false, 'error' => 'Unauthenticated.'], 401);

        if ($request->is('admin/*'))
            return redirect()->guest(route('admin.login'));

        return redirect()->guest(route('login'));
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
