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
        return $this->success(new ProfilePublicResource($this->profileService->getPublic($id)));
    }

    public function showPrivate(Request $request, string $id): JsonResponse
    {
        return $this->success(new ProfilePrivateResource($this->profileService->getPrivate($id)));
    }

    public function update(UpdateProfileRequest $request, string $id): JsonResponse
    {
        $profile = $this->profileService->update($id, $request->validated());

        return $this->success(new ProfilePrivateResource($profile));
    }
}
