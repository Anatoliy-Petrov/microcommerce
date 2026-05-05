<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(CategoryResource::collection(Category::all()));
    }

    public function products(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $products = $category->products()->with(['category', 'images', 'stock'])->where('is_active', true)->paginate(20);

        return $this->successPaginated(
            data:        ProductResource::collection($products->items()),
            total:       $products->total(),
            perPage:     $products->perPage(),
            currentPage: $products->currentPage(),
        );
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->success(new CategoryResource($category), 201);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return $this->success(new CategoryResource($category->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        Category::findOrFail($id)->delete();

        return $this->success(null, 204);
    }
}