<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireUserId
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-User-Id') === null) {
            return response()->json(
                ['data' => null, 'meta' => (object) [], 'errors' => [['message' => 'Unauthorized']]],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return $next($request);
    }
}
