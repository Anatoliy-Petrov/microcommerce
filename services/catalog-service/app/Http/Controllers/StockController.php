<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\JsonResponse;

final class StockController extends Controller
{
    public function show(string $id): JsonResponse
    {
        $stock = Stock::findOrFail($id);

        return $this->success([
            'productId'         => $stock->product_id,
            'quantity'          => $stock->quantity,
            'reservedQuantity'  => $stock->reserved_quantity,
            'availableQuantity' => $stock->available_quantity,
        ]);
    }
}