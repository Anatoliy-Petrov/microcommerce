<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class ImageService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_BYTES     = 5_242_880; // 5 MB

    public function delete(string $imageId): void
    {
        $image = ProductImage::findOrFail($imageId);

        Storage::disk('public')->delete($image->url);

        $wasPrimary  = $image->is_primary;
        $productId   = $image->product_id;
        $image->delete();

        if ($wasPrimary) {
            ProductImage::where('product_id', $productId)
                ->orderBy('sort_order')
                ->first()
                ?->update(['is_primary' => true]);
        }
    }

    public function upload(Product $product, UploadedFile $file): ProductImage
    {
        $mime = $file->getMimeType() ?? '';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Image must be jpeg, png, or webp');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Image must be smaller than 5 MB');
        }

        $filename = 'products/'.$product->id.'/'.Str::uuid().'.'.$file->getClientOriginalExtension();
        Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));

        $isPrimary = !$product->images()->where('is_primary', true)->exists();

        return ProductImage::create([
            'id'         => Str::uuid()->toString(),
            'product_id' => $product->id,
            'url'        => $filename,
            'sort_order' => $product->images()->max('sort_order') + 1,
            'is_primary' => $isPrimary,
        ]);
    }
}