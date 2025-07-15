<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
   public function register()
{
    $this->renderable(function (AuthorizationException $e, $request) {
        // Untuk AJAX/JSON, kembalikan JSON
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tidak memiliki izin untuk aksi ini.'], 403);
        }
        // Untuk request biasa, tampilkan view errors/403
        return response()->view('errors.403', [], 403);
    });

}
}