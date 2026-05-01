<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfilePrivateResource;
use App\Http\Resources\ProfilePublicResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $profile = $this->profileService->getPublic($id);

        return $this->success(new ProfilePublicResource($profile));
    }

    public function showPrivate(Request $request, string $id): JsonResponse
    {
        try {
            $profile = $this->profileService->getPrivate($id, (string) $request->header('X-User-Id'));
        } catch (\DomainException $e) {
            return $this->error([['message' => 'Forbidden']], 403);
        }

        return $this->success(new ProfilePrivateResource($profile));
    }

    public function update(UpdateProfileRequest $request, string $id): JsonResponse
    {
        try {
            $profile = $this->profileService->update($id, (string) $request->header('X-User-Id'), $request->validated());
        } catch (\DomainException $e) {
            return $this->error([['message' => 'Forbidden']], 403);
        }

        return $this->success(new ProfilePrivateResource($profile));
    }
}