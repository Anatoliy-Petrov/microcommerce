<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

final class Product extends Model
{
    use Searchable, SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'id', 'name', 'description', 'price', 'category_id', 'sku', 'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $product) {
            Storage::disk('public')->deleteDirectory('products/'.$product->id);
            $product->images()->delete();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'product_id', 'id');
    }

    public function toSearchableArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'sku'           => $this->sku,
            'category_name' => $this->category?->name,
            'price'         => (float) $this->price,
            'is_active'     => $this->is_active,
        ];
    }

    public function searchableAs(): string
    {
        return (string) config('services.elasticsearch.index.products', 'products');
    }
}