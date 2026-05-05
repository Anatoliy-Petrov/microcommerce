<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
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
        $query    = (string) $request->query('q', '');
        $perPage  = (int) $request->query('per_page', 20);
        $page     = (int) $request->query('page', 1);

        $results  = \App\Models\Product::search($query)->paginate($perPage, 'page', $page);

        return $this->successPaginated(
            data:        ProductResource::collection($results->items()),
            total:       $results->total(),
            perPage:     $results->perPage(),
            currentPage: $results->currentPage(),
        );
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return $this->success(new ProductResource($product), 201);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated());

        return $this->success(new ProductResource($product));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->productService->delete($id);

        return $this->success(null, 204);
    }
}