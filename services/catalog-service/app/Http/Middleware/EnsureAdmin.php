<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-User-Role') !== 'admin') {
            return response()->json(
                ['data' => null, 'meta' => (object) [], 'errors' => [['message' => 'Forbidden']]],
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}