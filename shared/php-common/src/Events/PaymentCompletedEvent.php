<?php

declare(strict_types=1);

namespace Microcommerce\Common\Events;

final class PaymentCompletedEvent extends DomainEvent
{
    public const NAME = 'payment.completed';

    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
        public readonly int    $amount,
        public readonly string $currency,
        public readonly string $provider,
        public readonly string $providerPaymentId,
        public readonly string $paidAt,
    ) {
        parent::__construct();
    }

    public static function getName(): string { return self::NAME; }

    public function toArray(): array
    {
        return [
            'orderId'           => $this->orderId,
            'userId'            => $this->userId,
            'amount'            => $this->amount,
            'currency'          => $this->currency,
            'provider'          => $this->provider,
            'providerPaymentId' => $this->providerPaymentId,
            'paidAt'            => $this->paidAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId:           $data['orderId'],
            userId:            $data['userId'],
            amount:            $data['amount'],
            currency:          $data['currency'],
            provider:          $data['provider'],
            providerPaymentId: $data['providerPaymentId'],
            paidAt:            $data['paidAt'],
        );
    }
}