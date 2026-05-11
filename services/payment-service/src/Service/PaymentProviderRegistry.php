<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PaymentProviderInterface;

final class PaymentProviderRegistry
{
    /** @var array<string, PaymentProviderInterface> */
    private array $providers = [];

    /** @param iterable<PaymentProviderInterface> $providers */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function get(string $name): PaymentProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown payment provider "%s". Available: %s', $name, implode(', ', array_keys($this->providers)))
            );
        }

        return $this->providers[$name];
    }

    /** @return string[] */
    public function available(): array
    {
        return array_keys($this->providers);
    }
}