<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Address */
final class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'label'     => $this->label,
            'line1'     => $this->line1,
            'line2'     => $this->line2,
            'city'      => $this->city,
            'postcode'  => $this->postcode,
            'country'   => $this->country,
            'isDefault' => $this->is_default,
        ];
    }
}