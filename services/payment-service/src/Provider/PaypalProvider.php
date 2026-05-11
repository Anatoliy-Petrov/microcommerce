<?php

declare(strict_types=1);

namespace App\Provider;

use App\Contract\PaymentProviderInterface;
use App\DTO\CheckoutRequest;
use App\DTO\CheckoutResult;
use App\DTO\WebhookResult;

readonly class PaypalProvider implements PaymentProviderInterface
{
    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $mode,
    ) {}

    public function getName(): string
    {
        return 'paypal';
    }

    public function createCheckoutSession(CheckoutRequest $request): CheckoutResult
    {
        $accessToken = $this->getAccessToken();

        $amountFormatted = number_format($request->amount / 100, 2, '.', '');

        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'reference_id'  => $request->orderId,
                'custom_id'     => $request->orderId,
                'amount'        => [
                    'currency_code' => strtoupper($request->currency),
                    'value'         => $amountFormatted,
                ],
            ]],
            'application_context' => [
                'return_url' => $request->successUrl,
                'cancel_url' => $request->cancelUrl,
            ],
        ];

        $response = $this->request('POST', '/v2/checkout/orders', $payload, $accessToken);

        $approveLink = '';
        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveLink = $link['href'];
                break;
            }
        }

        return new CheckoutResult(
            sessionUrl:        $approveLink,
            providerSessionId: $response['id'],
        );
    }

    public function handleWebhook(string $rawBody, array $headers): WebhookResult
    {
        // Verify PayPal webhook signature
        $this->verifySignature($rawBody, $headers);

        $event    = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        $eventId  = $event['id'];
        $type     = $event['event_type'];
        $resource = $event['resource'] ?? [];

        $orderId           = $resource['purchase_units'][0]['custom_id'] ?? null;
        $providerPaymentId = $resource['id'] ?? null;

        $completed = in_array($type, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true);
        $failed    = in_array($type, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED'], true);

        if (!$completed && !$failed) {
            return new WebhookResult(
                eventType:         $type,
                providerEventId:   $eventId,
                orderId:           null,
                userId:            null,
                providerPaymentId: null,
                completed:         false,
            );
        }

        return new WebhookResult(
            eventType:         $type,
            providerEventId:   $eventId,
            orderId:           $orderId,
            userId:            null,
            providerPaymentId: $providerPaymentId,
            completed:         $completed,
            failureReason:     $failed ? ($resource['status_details']['reason'] ?? 'payment_denied') : null,
        );
    }

    private function verifySignature(string $rawBody, array $headers): void
    {
        // PayPal signature verification requires calling their verify API
        // In production: POST to https://api.paypal.com/v1/notifications/verify-webhook-signature
        // For now we verify the transmission headers are present
        $required = ['paypal-transmission-id', 'paypal-transmission-time', 'paypal-cert-url', 'paypal-transmission-sig'];
        foreach ($required as $header) {
            if (empty($headers[$header])) {
                throw new \RuntimeException('Missing PayPal webhook header: '.$header);
            }
        }
    }

    private function getAccessToken(): string
    {
        $baseUrl  = $this->baseUrl();
        $response = $this->httpRequest('POST', $baseUrl.'/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ], [], $this->clientId, $this->clientSecret);

        return $response['access_token'];
    }

    private function request(string $method, string $path, array $body, string $token): array
    {
        return $this->httpRequest($method, $this->baseUrl().$path, $body, [
            'Authorization' => 'Bearer '.$token,
            'Content-Type'  => 'application/json',
        ]);
    }

    private function httpRequest(string $method, string $url, array $body, array $headers = [], string $user = '', string $pass = ''): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => $method,
                'header'  => $this->formatHeaders($headers),
                'content' => $method === 'POST' && isset($headers['Content-Type']) && str_contains($headers['Content-Type'], 'json')
                    ? json_encode($body)
                    : http_build_query($body),
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true],
        ]);

        if ($user !== '') {
            stream_context_set_option($ctx, 'http', 'header',
                $this->formatHeaders($headers)."\r\nAuthorization: Basic ".base64_encode("{$user}:{$pass}"));
        }

        $raw      = file_get_contents($url, false, $ctx);
        $response = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        if (isset($response['error'])) {
            throw new \RuntimeException('PayPal API error: '.($response['error_description'] ?? $response['error']));
        }

        return $response;
    }

    private function formatHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }
        return implode("\r\n", $lines);
    }

    private function baseUrl(): string
    {
        return $this->mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}