<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function checkout(Request $request): JsonResponse
    {
        $userId = (string) $request->headers->get('X-User-Id');
        if ($userId === '') {
            return $this->envelope(null, [['message' => 'Unauthorized']], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $body = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->envelope(null, [['message' => 'Invalid JSON']], Response::HTTP_BAD_REQUEST);
        }

        $provider   = (string) ($body['provider'] ?? '');
        $orderId    = (string) ($body['orderId'] ?? '');
        $amount     = (int) ($body['amount'] ?? 0);
        $currency   = (string) ($body['currency'] ?? 'usd');
        $successUrl = (string) ($body['successUrl'] ?? '');
        $cancelUrl  = (string) ($body['cancelUrl'] ?? '');

        if ($provider === '' || $orderId === '' || $amount <= 0) {
            return $this->envelope(null, [['message' => 'provider, orderId and amount are required']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->paymentService->createCheckoutSession(
                $provider, $orderId, $userId, $amount, $currency, $successUrl, $cancelUrl,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->envelope(null, [['message' => $e->getMessage()]], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->envelope(null, [['message' => 'Payment provider error: '.$e->getMessage()]], Response::HTTP_BAD_GATEWAY);
        }

        return $this->envelope([
            'sessionUrl'        => $result->sessionUrl,
            'providerSessionId' => $result->providerSessionId,
            'provider'          => $provider,
            'available'         => $this->paymentService->available(),
        ], [], Response::HTTP_CREATED);
    }

    public function show(Request $request, string $orderId): JsonResponse
    {
        $userId = (string) $request->headers->get('X-User-Id');
        if ($userId === '') {
            return $this->envelope(null, [['message' => 'Unauthorized']], Response::HTTP_UNAUTHORIZED);
        }

        $payment = $this->paymentService->findByOrderId($orderId);
        if ($payment === null) {
            return $this->envelope(null, [['message' => 'Payment not found']], Response::HTTP_NOT_FOUND);
        }

        if ($payment->getUserId() !== $userId && $request->headers->get('X-User-Role') !== 'admin') {
            return $this->envelope(null, [['message' => 'Forbidden']], Response::HTTP_FORBIDDEN);
        }

        return $this->envelope([
            'id'                => $payment->getId(),
            'orderId'           => $payment->getOrderId(),
            'provider'          => $payment->getProvider(),
            'status'            => $payment->getStatus(),
            'amount'            => $payment->getAmount(),
            'currency'          => $payment->getCurrency(),
            'providerPaymentId' => $payment->getProviderPaymentId(),
            'createdAt'         => $payment->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function envelope(mixed $data, array $errors = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse(['data' => $data, 'meta' => new \stdClass(), 'errors' => $errors], $status);
    }
}