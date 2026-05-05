<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data, 'meta' => (object) [], 'errors' => []], $status);
    }

    protected function successPaginated(mixed $data, int $total, int $perPage, int $currentPage): JsonResponse
    {
        return response()->json([
            'data'   => $data,
            'meta'   => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $currentPage],
            'errors' => [],
        ]);
    }

    protected function error(array $errors, int $status): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => (object) [], 'errors' => $errors], $status);
    }
}