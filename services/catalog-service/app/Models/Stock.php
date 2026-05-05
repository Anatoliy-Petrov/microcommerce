<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Stock extends Model
{
    protected $primaryKey  = 'product_id';
    public $incrementing   = false;
    public $timestamps     = false;
    protected $keyType     = 'string';
    protected $table = 'stock';

    protected $fillable = ['product_id', 'quantity', 'reserved_quantity'];

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }

    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}