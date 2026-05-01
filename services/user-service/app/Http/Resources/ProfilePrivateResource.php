<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Profile */
final class ProfilePrivateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'userId'      => $this->id,
            'displayName' => $this->display_name,
            'bio'         => $this->bio,
            'avatarUrl'   => $this->avatar_url,
            'createdAt'   => $this->created_at?->toIso8601String(),
            'updatedAt'   => $this->updated_at?->toIso8601String(),
        ];
    }
}