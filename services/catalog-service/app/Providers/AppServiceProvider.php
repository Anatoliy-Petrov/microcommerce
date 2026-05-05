<?php

declare(strict_types=1);

namespace App\Providers;

use App\Search\ElasticsearchEngine;
use App\Services\EventConsumerService;
use App\Services\EventPublisherService;
use App\Services\StockService;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ElasticsearchEngine::class, function () {
            $cfg    = config('services.elasticsearch');
            $host   = sprintf('%s://%s:%s@%s:%d', $cfg['scheme'], $cfg['user'], $cfg['password'], $cfg['host'], $cfg['port']);
            $client = ClientBuilder::create()->setHosts([$host])->build();

            return new ElasticsearchEngine($client, (string) $cfg['index']['products']);
        });

        $this->app->bind(EventPublisherService::class, function () {
            return new EventPublisherService((string) config('services.rabbitmq.url'));
        });

        $this->app->bind(EventConsumerService::class, function ($app) {
            return new EventConsumerService(
                rabbitmqUrl:  (string) config('services.rabbitmq.url'),
                stockService: $app->make(StockService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->app->make(EngineManager::class)->extend('elasticsearch', function () {
            return $this->app->make(ElasticsearchEngine::class);
        });
    }
}