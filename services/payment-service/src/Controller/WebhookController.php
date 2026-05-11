<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends AbstractController
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function receive(Request $request, string $provider): JsonResponse
    {
        $rawBody = $request->getContent();
        $headers = array_map(
            fn (array $v) => $v[0],
            $request->headers->all(),
        );

        try {
            $this->paymentService->handleWebhook($provider, $rawBody, $headers);
        } catch (\InvalidArgumentException $e) {
            // Unknown provider
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $e) {
            // Signature mismatch or bad payload
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            // Processing error — return 200 so provider doesn't retry a poison pill
            return new JsonResponse(['error' => 'processing_error'], Response::HTTP_OK);
        }

        return new JsonResponse(['received' => true], Response::HTTP_OK);
    }
}