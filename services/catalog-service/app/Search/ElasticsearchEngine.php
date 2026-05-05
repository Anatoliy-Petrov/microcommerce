<?php

declare(strict_types=1);

namespace App\Search;

use Elastic\Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

final class ElasticsearchEngine extends Engine
{
    public function __construct(
        private readonly Client $client,
        private readonly string $index,
    ) {}

    public function update($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $params = ['body' => []];
        foreach ($models as $model) {
            $params['body'][] = ['index' => ['_index' => $this->index, '_id' => $model->getScoutKey()]];
            $params['body'][] = $model->toSearchableArray();
        }

        $this->client->bulk($params);
    }

    public function delete($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $params = ['body' => []];
        foreach ($models as $model) {
            $params['body'][] = ['delete' => ['_index' => $this->index, '_id' => $model->getScoutKey()]];
        }

        $this->client->bulk($params);
    }

    public function search(Builder $builder): mixed
    {
        return $this->performSearch($builder);
    }

    public function paginate(Builder $builder, $perPage, $page): mixed
    {
        return $this->performSearch($builder, [
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ]);
    }

    public function mapIds($results): \Illuminate\Support\Collection
    {
        return collect(array_column($results['hits']['hits'], '_id'));
    }

    public function map(Builder $builder, $results, $model): Collection
    {
        if ((int) $results['hits']['total']['value'] === 0) {
            return $model->newCollection();
        }

        $ids = $this->mapIds($results)->all();

        return $model->getScoutModelsByIds($builder, $ids);
    }

    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        return LazyCollection::make($this->map($builder, $results, $model)->all());
    }

    public function getTotalCount($results): int
    {
        return (int) $results['hits']['total']['value'];
    }

    public function flush($model): void
    {
        try {
            $this->client->indices()->delete(['index' => $this->index]);
        } catch (\Throwable) {
            // Index may not exist yet
        }
    }

    public function createIndex($name, array $options = []): mixed
    {
        try {
            $this->client->indices()->create(['index' => $name]);
        } catch (\Throwable) {
            // Index may already exist
        }

        return null;
    }

    public function deleteIndex($name): mixed
    {
        try {
            $this->client->indices()->delete(['index' => $name]);
        } catch (\Throwable) {
            // Index may not exist
        }

        return null;
    }

    private function performSearch(Builder $builder, array $options = []): array
    {
        $params = [
            'index' => $this->index,
            'body'  => [
                'query' => $builder->query
                    ? [
                        'multi_match' => [
                            'query'     => $builder->query,
                            'fields'    => ['name^3', 'sku^2', 'description', 'category_name'],
                            'type'      => 'best_fields',
                            'fuzziness' => 'AUTO',
                        ],
                    ]
                    : ['match_all' => (object) []],
                ...$options,
            ],
        ];

        return $this->client->search($params)->asArray();
    }
}