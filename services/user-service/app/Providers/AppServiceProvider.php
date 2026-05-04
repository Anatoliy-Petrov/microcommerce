<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\EventConsumerService::class, function ($app) {
            return new \App\Services\EventConsumerService(
                rabbitmqUrl:    (string) config('services.rabbitmq.url'),
                profileService: $app->make(\App\Services\ProfileService::class),
            );
        });

        $this->app->bind(\App\Services\AvatarService::class, function () {
            return new \App\Services\AvatarService(
                disk: (string) env('AVATAR_DISK', 'public'),
            );
        });
    }

    public function boot(): void {}
}