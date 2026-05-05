<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\EventPublisherService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EventPublisherService::class, function () {
            return new EventPublisherService((string) config('services.rabbitmq.url'));
        });
    }

    public function boot(): void {}
}
