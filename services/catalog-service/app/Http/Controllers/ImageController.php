<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ImageController extends Controller
{
    public function __construct(private readonly ImageService $imageService) {}

    public function store(Request $request, string $id): JsonResponse
    {
        $file = $request->file('image');
        if ($file === null) {
            return $this->error([['field' => 'image', 'message' => 'Image file is required']], 422);
        }

        $product = Product::findOrFail($id);

        try {
            $image = $this->imageService->upload($product, $file);
        } catch (\InvalidArgumentException $e) {
            return $this->error([['field' => 'image', 'message' => $e->getMessage()]], 422);
        }

        return $this->success([
            'id'        => $image->id,
            'url'       => \Illuminate\Support\Facades\Storage::disk('public')->url($image->url),
            'isPrimary' => $image->is_primary,
            'sortOrder' => $image->sort_order,
        ], 201);
    }

    public function destroy(Request $request, string $id, string $imageId): JsonResponse
    {
        $this->imageService->delete($imageId);

        return $this->success(null, 204);
    }
}