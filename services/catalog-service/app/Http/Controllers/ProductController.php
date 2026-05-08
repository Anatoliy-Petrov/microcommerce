<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->productService->list(
            perPage:    (int) $request->query('per_page', 20),
            categoryId: $request->query('category_id') ? (int) $request->query('category_id') : null,
        );

        return $this->successPaginated(
            data:        ProductResource::collection($paginator->items()),
            total:       $paginator->total(),
            perPage:     $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function show(string $id): JsonResponse
    {
        return $this->success(new ProductResource($this->productService->findOrFail($id)));
    }

    public function search(Request $request): JsonResponse
    {
        $query   = (string) $request->query('q', '');
        $perPage = (int) $request->query('per_page', 20);
        $page    = (int) $request->query('page', 1);

        $results = Product::search($query)->paginate($perPage, 'page', $page);

        return $this->successPaginated(
            data:        ProductResource::collection($results->items()),
            total:       $results->total(),
            perPage:     $results->perPage(),
            currentPage: $results->currentPage(),
        );
    }
}