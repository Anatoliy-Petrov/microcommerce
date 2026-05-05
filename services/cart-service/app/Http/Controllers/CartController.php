<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function show(Request $request, string $userId): JsonResponse
    {
        return $this->success($this->cartService->getCart($userId));
    }

    public function addItem(AddItemRequest $request, string $userId): JsonResponse
    {
        try {
            $this->cartService->addItem(
                userId:     $userId,
                productId:  $request->validated('product_id'),
                quantity:   (int) $request->validated('quantity', 1),
            );
        } catch (\RuntimeException $e) {
            return $this->error([['message' => $e->getMessage()]], 422);
        }

        return $this->success($this->cartService->getCart($userId));
    }

    public function updateItem(UpdateItemRequest $request, string $userId, string $productId): JsonResponse
    {
        try {
            $this->cartService->updateItem($userId, $productId, $request->validated('quantity'));
        } catch (\DomainException $e) {
            return $this->error([['message' => $e->getMessage()]], 404);
        }

        return $this->success($this->cartService->getCart($userId));
    }

    public function removeItem(Request $request, string $userId, string $productId): JsonResponse
    {
        $this->cartService->removeItem($userId, $productId);

        return $this->success($this->cartService->getCart($userId));
    }

    public function clear(Request $request, string $userId): JsonResponse
    {
        $this->cartService->clearCart($userId);

        return $this->success(null, 204);
    }

    public function checkout(Request $request, string $userId): JsonResponse
    {
        try {
            $cart = $this->cartService->checkout($userId);
        } catch (\DomainException $e) {
            return $this->error([['message' => $e->getMessage()]], 422);
        }

        return $this->success($cart);
    }
}
