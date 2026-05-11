<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CheckoutRequest;
use App\DTO\CheckoutResult;
use App\DTO\WebhookResult;
use App\Entity\Payment;
use App\Entity\WebhookEvent;
use App\Repository\PaymentRepository;
use App\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Microcommerce\Common\Events\PaymentCompletedEvent;
use Microcommerce\Common\Events\PaymentFailedEvent;
use Symfony\Component\Uid\Uuid;

readonly class PaymentService
{
    public function __construct(
        private PaymentProviderRegistry  $registry,
        private PaymentRepository        $paymentRepository,
        private WebhookEventRepository   $webhookEventRepository,
        private EntityManagerInterface   $em,
        private EventPublisherService    $eventPublisher,
    ) {}

    public function createCheckoutSession(
        string $providerName,
        string $orderId,
        string $userId,
        int    $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutResult {
        $provider = $this->registry->get($providerName);

        $payment = new Payment(
            id:       Uuid::v7()->toRfc4122(),
            orderId:  $orderId,
            userId:   $userId,
            provider: $providerName,
            amount:   $amount,
            currency: $currency,
        );

        $request = new CheckoutRequest($orderId, $userId, $amount, $currency, $successUrl, $cancelUrl);
        $result  = $provider->createCheckoutSession($request);

        $payment->setProviderSessionId($result->providerSessionId);
        $this->paymentRepository->save($payment, true);

        return $result;
    }

    public function handleWebhook(string $providerName, string $rawBody, array $headers): void
    {
        $provider = $this->registry->get($providerName);
        $result   = $provider->handleWebhook($rawBody, $headers);

        // Idempotency: skip already-processed events
        if ($this->webhookEventRepository->isAlreadyProcessed($providerName, $result->providerEventId)) {
            return;
        }

        $webhookEvent = new WebhookEvent($providerName, $result->providerEventId, $result->eventType);

        try {
            if ($result->orderId !== null) {
                $this->processWebhookResult($result, $providerName);
            }
            $this->webhookEventRepository->save($webhookEvent, true);
        } catch (\Throwable $e) {
            $webhookEvent->setError($e->getMessage());
            $this->webhookEventRepository->save($webhookEvent, true);
            throw $e;
        }
    }

    public function findByOrderId(string $orderId): ?Payment
    {
        return $this->paymentRepository->findByOrderId($orderId);
    }

    public function available(): array
    {
        return $this->registry->available();
    }

    private function processWebhookResult(WebhookResult $result, string $provider): void
    {
        $payment = $this->paymentRepository->findByOrderId($result->orderId);

        if ($result->completed) {
            if ($payment !== null) {
                $payment->markCompleted($result->providerPaymentId ?? '');
                $this->em->flush();
            }

            $this->eventPublisher->publish(new PaymentCompletedEvent(
                orderId:           $result->orderId,
                userId:            $result->userId ?? '',
                amount:            $payment?->getAmount() ?? 0,
                currency:          $payment?->getCurrency() ?? 'usd',
                provider:          $provider,
                providerPaymentId: $result->providerPaymentId ?? '',
                paidAt:            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ));
        } else {
            if ($payment !== null) {
                $payment->markFailed();
                $this->em->flush();
            }

            $this->eventPublisher->publish(new PaymentFailedEvent(
                orderId:  $result->orderId,
                userId:   $result->userId ?? '',
                provider: $provider,
                reason:   $result->failureReason ?? 'unknown',
                failedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ));
        }
    }
}