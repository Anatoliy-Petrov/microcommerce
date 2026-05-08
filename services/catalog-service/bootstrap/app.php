<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'require.user_id' => \App\Http\Middleware\RequireUserId::class,
            'admin'           => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, Request $request) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $errors[] = ['field' => $field, 'message' => $message];
                }
            }
            return response()->json(['data' => null, 'meta' => (object) [], 'errors' => $errors], 422);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return response()->json(['data' => null, 'meta' => (object) [], 'errors' => [['message' => 'Not found']]], 404);
        });
    })
    ->create();