<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Profile;

final readonly class ProfileService
{
    public function getPublic(string $userId): Profile
    {
        return Profile::findOrFail($userId);
    }

    public function getPrivate(string $userId): Profile
    {
        return Profile::findOrFail($userId);
    }

    public function update(string $userId, array $data): Profile
    {
        $profile = Profile::findOrFail($userId);
        $profile->update($data);

        return $profile->fresh();
    }

    public function createFromEvent(string $userId): Profile
    {
        return Profile::firstOrCreate(['id' => $userId]);
    }
}
