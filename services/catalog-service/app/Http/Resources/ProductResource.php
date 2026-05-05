<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => (string) $this->price,
            'sku'         => $this->sku,
            'isActive'    => $this->is_active,
            'category'    => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'images'      => $this->whenLoaded('images', fn () =>
                $this->images->map(fn ($img) => [
                    'url'       => \Illuminate\Support\Facades\Storage::disk('public')->url($img->url),
                    'isPrimary' => $img->is_primary,
                    'sortOrder' => $img->sort_order,
                ])->all()
            ),
            'stock'       => $this->whenLoaded('stock', fn () => $this->stock ? [
                'quantity'          => $this->stock->quantity,
                'reservedQuantity'  => $this->stock->reserved_quantity,
                'availableQuantity' => $this->stock->available_quantity,
            ] : null),
            'createdAt'   => $this->created_at?->toIso8601String(),
        ];
    }
}