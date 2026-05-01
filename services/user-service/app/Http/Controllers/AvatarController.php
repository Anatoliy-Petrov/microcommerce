<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AvatarController extends Controller
{
    public function __construct(private readonly AvatarService $avatarService) {}

    public function store(Request $request, string $id): JsonResponse
    {
        if ($id !== (string) $request->header('X-User-Id')) {
            return $this->error([['message' => 'Forbidden']], 403);
        }

        $file = $request->file('avatar');
        if ($file === null) {
            return $this->error([['field' => 'avatar', 'message' => 'Avatar file is required']], 422);
        }

        $profile = Profile::findOrFail($id);

        try {
            $url = $this->avatarService->upload($profile, $file);
        } catch (\InvalidArgumentException $e) {
            return $this->error([['field' => 'avatar', 'message' => $e->getMessage()]], 422);
        }

        return $this->success(['avatarUrl' => $url]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if ($id !== (string) $request->header('X-User-Id')) {
            return $this->error([['message' => 'Forbidden']], 403);
        }

        $profile = Profile::findOrFail($id);
        $this->avatarService->delete($profile);

        return $this->success(null, 204);
    }
}