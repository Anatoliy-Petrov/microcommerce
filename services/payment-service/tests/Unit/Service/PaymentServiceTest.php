<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\CheckoutRequest;
use App\DTO\CheckoutResult;
use App\DTO\WebhookResult;
use App\Contract\PaymentProviderInterface;
use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\WebhookEventRepository;
use App\Service\EventPublisherService;
use App\Service\PaymentProviderRegistry;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Microcommerce\Common\Events\PaymentCompletedEvent;
use Microcommerce\Common\Events\PaymentFailedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentServiceTest extends TestCase
{
    private PaymentProviderInterface&MockObject $provider;
    private PaymentRepository&MockObject        $paymentRepository;
    private WebhookEventRepository&MockObject   $webhookEventRepository;
    private EntityManagerInterface&MockObject   $em;
    private EventPublisherService&MockObject    $eventPublisher;
    private PaymentService $service;

    protected function setUp(): void
    {
        $this->provider               = $this->createMock(PaymentProviderInterface::class);
        $this->paymentRepository      = $this->createMock(PaymentRepository::class);
        $this->webhookEventRepository = $this->createMock(WebhookEventRepository::class);
        $this->em                     = $this->createMock(EntityManagerInterface::class);
        $this->eventPublisher         = $this->createMock(EventPublisherService::class);

        $this->provider->method('getName')->willReturn('paypal');

        $registry = new PaymentProviderRegistry([$this->provider]);

        $this->service = new PaymentService(
            $registry,
            $this->paymentRepository,
            $this->webhookEventRepository,
            $this->em,
            $this->eventPublisher,
        );
    }

    public function testCreateCheckoutSessionPersistsPaymentAndReturnsResult(): void
    {
        $this->provider->method('createCheckoutSession')->willReturn(
            new CheckoutResult('https://paypal.com/checkout/session-1', 'session-1')
        );
        $this->paymentRepository->expects($this->once())->method('save');

        $result = $this->service->createCheckoutSession('paypal', 'order-1', 'user-1', 1000, 'usd', 'https://ok', 'https://cancel');

        $this->assertSame('https://paypal.com/checkout/session-1', $result->sessionUrl);
        $this->assertSame('session-1', $result->providerSessionId);
    }

    public function testHandleWebhookSkipsAlreadyProcessedEvent(): void
    {
        $this->webhookEventRepository->method('isAlreadyProcessed')->willReturn(true);
        $this->provider->method('handleWebhook')->willReturn(new WebhookResult('event', 'evt-1', null, null, null, true));

        $this->eventPublisher->expects($this->never())->method('publish');

        $this->service->handleWebhook('paypal', '{}', []);
    }

    public function testHandleWebhookCompletedPublishesPaymentCompletedEvent(): void
    {
        $payment = new Payment('pay-1', 'order-1', 'user-1', 'paypal', 1000, 'usd');

        $this->webhookEventRepository->method('isAlreadyProcessed')->willReturn(false);
        $this->provider->method('handleWebhook')->willReturn(
            new WebhookResult('CHECKOUT.ORDER.APPROVED', 'evt-1', 'order-1', 'user-1', 'paypal-pay-1', true)
        );
        $this->paymentRepository->method('findByOrderId')->willReturn($payment);

        $this->eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(PaymentCompletedEvent::class));

        $this->service->handleWebhook('paypal', '{}', []);
    }

    public function testHandleWebhookFailedPublishesPaymentFailedEvent(): void
    {
        $payment = new Payment('pay-1', 'order-1', 'user-1', 'paypal', 1000, 'usd');

        $this->webhookEventRepository->method('isAlreadyProcessed')->willReturn(false);
        $this->provider->method('handleWebhook')->willReturn(
            new WebhookResult('PAYMENT.CAPTURE.DENIED', 'evt-2', 'order-1', 'user-1', null, false, 'INSTRUMENT_DECLINED')
        );
        $this->paymentRepository->method('findByOrderId')->willReturn($payment);

        $this->eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(PaymentFailedEvent::class));

        $this->service->handleWebhook('paypal', '{}', []);
    }

    public function testRegistryThrowsOnUnknownProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createCheckoutSession('stripe', 'order-1', 'user-1', 1000, 'usd', '', '');
    }
}