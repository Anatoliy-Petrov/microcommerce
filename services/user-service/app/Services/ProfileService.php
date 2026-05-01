<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Profile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ProfileService
{
    public function getPublic(string $userId): Profile
    {
        return Profile::findOrFail($userId);
    }

    public function getPrivate(string $userId, string $requestingUserId): Profile
    {
        if ($userId !== $requestingUserId) {
            throw new \DomainException('Forbidden', 403);
        }

        return Profile::findOrFail($userId);
    }

    public function update(string $userId, string $requestingUserId, array $data): Profile
    {
        if ($userId !== $requestingUserId) {
            throw new \DomainException('Forbidden', 403);
        }

        $profile = Profile::findOrFail($userId);
        $profile->update($data);

        return $profile->fresh();
    }

    public function createFromEvent(string $userId): Profile
    {
        return Profile::firstOrCreate(['id' => $userId]);
    }
}