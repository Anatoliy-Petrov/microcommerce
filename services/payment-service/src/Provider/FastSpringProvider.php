<?php

declare(strict_types=1);

namespace App\Provider;

use App\Contract\PaymentProviderInterface;
use App\DTO\CheckoutRequest;
use App\DTO\CheckoutResult;
use App\DTO\WebhookResult;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FastSpringProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly string              $apiUrl,
        private readonly string              $username,
        private readonly string              $password,
        private readonly string              $hmacSecret,
        private readonly string              $storefront,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function getName(): string
    {
        return 'fastspring';
    }

    public function createCheckoutSession(CheckoutRequest $request): CheckoutResult
    {
        $amountFormatted = number_format($request->amount / 100, 2, '.', '');

        $response = $this->httpClient->request('POST', $this->apiUrl.'/sessions', [
            'auth_basic' => [$this->username, $this->password],
            'json'       => [
                'items' => [[
                    'product'  => $request->orderId,
                    'quantity' => 1,
                    'pricing'  => ['price' => [strtoupper($request->currency) => $amountFormatted]],
                ]],
                'tags' => ['orderId' => $request->orderId, 'userId' => $request->userId],
            ],
        ]);

        $data      = $response->toArray();
        $sessionId = $data['id'] ?? throw new \RuntimeException('FastSpring: missing session id');

        return new CheckoutResult(
            sessionUrl:        "https://{$this->storefront}.onfastspring.com/popup-{$sessionId}",
            providerSessionId: $sessionId,
        );
    }

    public function handleWebhook(string $rawBody, array $headers): WebhookResult
    {
        $this->verifySignature($rawBody, $headers);

        $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        $events  = $payload['events'] ?? [];

        if (empty($events)) {
            return new WebhookResult(
                eventType:         'unknown',
                providerEventId:   uniqid('fs_', true),
                orderId:           null,
                userId:            null,
                providerPaymentId: null,
                completed:         false,
            );
        }

        $event   = $events[0];
        $type    = $event['type'];
        $eventId = $event['id'];
        $data    = $event['data'] ?? [];
        $tags    = $data['tags'] ?? [];

        $completed = $type === 'order.completed';
        $failed    = $type === 'order.failed';

        return new WebhookResult(
            eventType:         $type,
            providerEventId:   $eventId,
            orderId:           $tags['orderId'] ?? null,
            userId:            $tags['userId'] ?? null,
            providerPaymentId: $data['reference'] ?? null,
            completed:         $completed,
            failureReason:     $failed ? ($data['reason'] ?? 'order_failed') : null,
        );
    }

    private function verifySignature(string $rawBody, array $headers): void
    {
        $signature = $headers['x-fs-signature'] ?? $headers['X-FS-Signature'] ?? null;
        if ($signature === null) {
            throw new \RuntimeException('Missing FastSpring signature header');
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, $this->hmacSecret, true));
        if (!hash_equals($expected, $signature)) {
            throw new \RuntimeException('FastSpring webhook signature mismatch');
        }
    }
}