<?php

declare(strict_types=1);

namespace App\Tests\Unit\Provider;

use App\Provider\PaypalProvider;
use PHPUnit\Framework\TestCase;

final class PaypalProviderTest extends TestCase
{
    private PaypalProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new PaypalProvider('client-id', 'client-secret', 'sandbox');
    }

    public function testGetNameReturnsPaypal(): void
    {
        $this->assertSame('paypal', $this->provider->getName());
    }

    public function testHandleWebhookRejectsMissingSignatureHeaders(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Missing PayPal webhook header/');

        $this->provider->handleWebhook('{}', []);
    }

    public function testHandleWebhookReturnsNonActionableResultForUnknownEventType(): void
    {
        $headers = [
            'paypal-transmission-id'   => 'id-1',
            'paypal-transmission-time' => '2026-01-01',
            'paypal-cert-url'          => 'https://cert.paypal.com',
            'paypal-transmission-sig'  => 'sig-1',
        ];

        $body = json_encode([
            'id'         => 'evt-1',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource'   => [],
        ]);

        $result = $this->provider->handleWebhook($body, $headers);

        $this->assertFalse($result->completed);
        $this->assertNull($result->orderId);
    }

    public function testHandleWebhookMapsCheckoutApprovedToCompleted(): void
    {
        $headers = [
            'paypal-transmission-id'   => 'id-1',
            'paypal-transmission-time' => '2026-01-01',
            'paypal-cert-url'          => 'https://cert.paypal.com',
            'paypal-transmission-sig'  => 'sig-1',
        ];

        $body = json_encode([
            'id'         => 'evt-2',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource'   => [
                'id'             => 'PAYPAL-PAY-1',
                'purchase_units' => [['custom_id' => 'order-abc']],
            ],
        ]);

        $result = $this->provider->handleWebhook($body, $headers);

        $this->assertTrue($result->completed);
        $this->assertSame('order-abc', $result->orderId);
        $this->assertSame('PAYPAL-PAY-1', $result->providerPaymentId);
    }
}