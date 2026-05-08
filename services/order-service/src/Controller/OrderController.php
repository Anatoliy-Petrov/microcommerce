<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService    $orderService,
        private readonly OrderRepository $orderRepository,
    ) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $userId = (string) $request->headers->get('X-User-Id');

        try {
            $order = $this->orderService->findOrFail($id);
        } catch (\DomainException) {
            return $this->notFound();
        }

        if ($order->getUserId() !== $userId && $request->headers->get('X-User-Role') !== 'admin') {
            return $this->forbidden();
        }

        return $this->envelope($this->serializeOrder($order));
    }

    public function list(Request $request): JsonResponse
    {
        $userId  = (string) $request->headers->get('X-User-Id');
        $page    = max(1, (int) $request->query->get('page', 1));
        $perPage = min(50, max(1, (int) $request->query->get('per_page', 20)));

        $paginator = $this->orderRepository->findByUserId($userId, $page, $perPage);
        $orders    = iterator_to_array($paginator);

        return $this->envelope([
            'items'       => array_map(fn (Order $o) => $this->serializeOrder($o), $orders),
            'total'       => count($paginator),
            'page'        => $page,
            'perPage'     => $perPage,
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $userId = (string) $request->headers->get('X-User-Id');

        try {
            $order = $this->orderService->findOrFail($id);
        } catch (\DomainException) {
            return $this->notFound();
        }

        if ($order->getUserId() !== $userId) {
            return $this->forbidden();
        }

        try {
            $order = $this->orderService->cancel($id, 'user_request');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->envelope($this->serializeOrder($order));
    }

    public function ship(Request $request, string $id): JsonResponse
    {
        if ($request->headers->get('X-User-Role') !== 'admin') {
            return $this->forbidden();
        }

        $tracking = (string) ($request->toArray()['trackingNumber'] ?? '');
        if ($tracking === '') {
            return $this->error('trackingNumber is required', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $order = $this->orderService->ship($id, $tracking);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->envelope($this->serializeOrder($order));
    }

    public function timeline(Request $request, string $id): JsonResponse
    {
        $userId = (string) $request->headers->get('X-User-Id');

        try {
            $order = $this->orderService->findOrFail($id);
        } catch (\DomainException) {
            return $this->notFound();
        }

        if ($order->getUserId() !== $userId && $request->headers->get('X-User-Role') !== 'admin') {
            return $this->forbidden();
        }

        $timeline = array_map(fn ($t) => [
            'from'        => $t->getFromState(),
            'to'          => $t->getToState(),
            'triggeredBy' => $t->getTriggeredBy(),
            'createdAt'   => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], $order->getTransitions()->toArray());

        return $this->envelope($timeline);
    }

    // --- Helpers ---

    private function serializeOrder(Order $order): array
    {
        return [
            'id'        => $order->getId(),
            'userId'    => $order->getUserId(),
            'status'    => $order->getStatus(),
            'subtotal'  => $order->getSubtotal(),
            'total'     => $order->getTotal(),
            'items'     => array_map(fn ($i) => $i->toArray(), $order->getItems()->toArray()),
            'createdAt' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $order->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function envelope(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(['data' => $data, 'meta' => new \stdClass(), 'errors' => []], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['data' => null, 'meta' => new \stdClass(), 'errors' => [['message' => $message]]], $status);
    }

    private function notFound(): JsonResponse
    {
        return $this->error('Order not found', Response::HTTP_NOT_FOUND);
    }

    private function forbidden(): JsonResponse
    {
        return $this->error('Forbidden', Response::HTTP_FORBIDDEN);
    }
}