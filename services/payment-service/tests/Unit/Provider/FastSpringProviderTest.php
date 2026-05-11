<?php

declare(strict_types=1);

namespace App\Tests\Unit\Provider;

use App\Provider\FastSpringProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class FastSpringProviderTest extends TestCase
{
    private string $hmacSecret = 'test-secret';

    private function makeProvider(): FastSpringProvider
    {
        return new FastSpringProvider(
            apiUrl:     'https://api.fastspring.com',
            username:   'user',
            password:   'pass',
            hmacSecret: $this->hmacSecret,
            storefront: 'mystore',
            httpClient: new MockHttpClient(),
        );
    }

    public function testGetNameReturnsFastspring(): void
    {
        $this->assertSame('fastspring', $this->makeProvider()->getName());
    }

    public function testHandleWebhookRejectsMissingSignature(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Missing FastSpring signature/');

        $this->makeProvider()->handleWebhook('{}', []);
    }

    public function testHandleWebhookRejectsInvalidSignature(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/signature mismatch/');

        $this->makeProvider()->handleWebhook('{}', ['x-fs-signature' => 'bad-sig']);
    }

    public function testHandleWebhookAcceptsValidSignature(): void
    {
        $body      = json_encode(['events' => []]);
        $signature = base64_encode(hash_hmac('sha256', $body, $this->hmacSecret, true));

        $result = $this->makeProvider()->handleWebhook($body, ['x-fs-signature' => $signature]);

        $this->assertFalse($result->completed);
    }

    public function testHandleWebhookMapsOrderCompletedToCompleted(): void
    {
        $body = json_encode([
            'events' => [[
                'id'   => 'fs-evt-1',
                'type' => 'order.completed',
                'data' => [
                    'reference' => 'FS-REF-1',
                    'tags'      => ['orderId' => 'order-xyz', 'userId' => 'user-1'],
                ],
            ]],
        ]);
        $signature = base64_encode(hash_hmac('sha256', $body, $this->hmacSecret, true));

        $result = $this->makeProvider()->handleWebhook($body, ['x-fs-signature' => $signature]);

        $this->assertTrue($result->completed);
        $this->assertSame('order-xyz', $result->orderId);
        $this->assertSame('FS-REF-1', $result->providerPaymentId);
    }
}