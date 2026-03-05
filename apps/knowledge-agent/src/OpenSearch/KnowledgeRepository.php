<?php

declare(strict_types=1);

namespace App\OpenSearch;

use OpenSearch\Client;

final class KnowledgeRepository
{
    public function __construct(
        private readonly Client $client,
        private readonly string $indexName,
    ) {
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function index(array $entry): string
    {
        $entry['created_at'] = $entry['created_at'] ?? (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601);
        $entry['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601);

        $id = $entry['id'] ?? null;
        $params = [
            'index' => $this->indexName,
            'body' => $entry,
        ];

        if (null !== $id && '' !== $id) {
            $params['id'] = (string) $id;
        }

        /** @var array<string, mixed> $response */
        $response = $this->client->index($params);

        return (string) $response['_id'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        try {
            /** @var array<string, mixed> $response */
            $response = $this->client->get([
                'index' => $this->indexName,
                'id' => $id,
            ]);

            if (!(bool) $response['found']) {
                return null;
            }

            /** @var array<string, mixed> $source */
            $source = $response['_source'];
            $source['id'] = (string) $response['_id'];

            return $source;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function update(string $id, array $entry): bool
    {
        $entry['updated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601);

        try {
            $this->client->update([
                'index' => $this->indexName,
                'id' => $id,
                'body' => ['doc' => $entry],
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $this->client->delete([
                'index' => $this->indexName,
                'id' => $id,
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $query, string $mode = 'hybrid', array $options = []): array
    {
        $size = (int) ($options['size'] ?? 10);

        $searchQuery = match ($mode) {
            'keyword' => $this->buildKeywordQuery($query),
            'vector' => $this->buildVectorQuery($query, $options),
            default => $this->buildHybridQuery($query, $options),
        };

        try {
            /** @var array<string, mixed> $response */
            $response = $this->client->search([
                'index' => $this->indexName,
                'body' => array_merge($searchQuery, ['size' => $size]),
            ]);
        } catch (\Throwable) {
            return [];
        }

        /** @var array<string, mixed> $hits */
        $hits = $response['hits'] ?? [];
        /** @var list<array<string, mixed>> $hitList */
        $hitList = $hits['hits'] ?? [];

        return array_map(static function (array $hit): array {
            /** @var array<string, mixed> $source */
            $source = $hit['_source'] ?? [];
            $source['id'] = (string) $hit['_id'];

            return $source;
        }, $hitList);
    }

    /**
     * @return array<string, mixed>
     */
    public function aggregateTree(): array
    {
        try {
            /** @var array<string, mixed> $response */
            $response = $this->client->search([
                'index' => $this->indexName,
                'body' => [
                    'size' => 0,
                    'aggs' => [
                        'tree_paths' => [
                            'terms' => [
                                'field' => 'tree_path',
                                'size' => 1000,
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable) {
            return [];
        }

        /** @var array<string, mixed> $aggregations */
        $aggregations = $response['aggregations'] ?? [];
        /** @var array<string, mixed> $treePaths */
        $treePaths = $aggregations['tree_paths'] ?? [];
        /** @var list<array<string, mixed>> $buckets */
        $buckets = $treePaths['buckets'] ?? [];

        $tree = [];
        foreach ($buckets as $bucket) {
            $path = (string) $bucket['key'];
            $count = (int) $bucket['doc_count'];
            $tree[$path] = $count;
        }

        return $tree;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    public function listEntries(array $filters = [], int $from = 0, int $size = 20): array
    {
        $must = [];

        if (isset($filters['tree_path']) && '' !== $filters['tree_path']) {
            $must[] = ['term' => ['tree_path' => $filters['tree_path']]];
        }

        if (isset($filters['category']) && '' !== $filters['category']) {
            $must[] = ['term' => ['category' => $filters['category']]];
        }

        if (isset($filters['tags']) && \is_array($filters['tags']) && [] !== $filters['tags']) {
            $must[] = ['terms' => ['tags' => $filters['tags']]];
        }

        $query = [] !== $must ? ['bool' => ['must' => $must]] : ['match_all' => (object) []];

        try {
            /** @var array<string, mixed> $response */
            $response = $this->client->search([
                'index' => $this->indexName,
                'body' => [
                    'query' => $query,
                    'from' => $from,
                    'size' => $size,
                    'sort' => [['created_at' => ['order' => 'desc']]],
                ],
            ]);
        } catch (\Throwable) {
            return [];
        }

        /** @var array<string, mixed> $hits */
        $hits = $response['hits'] ?? [];
        /** @var list<array<string, mixed>> $hitList */
        $hitList = $hits['hits'] ?? [];

        return array_map(static function (array $hit): array {
            /** @var array<string, mixed> $source */
            $source = $hit['_source'] ?? [];
            $source['id'] = (string) $hit['_id'];

            return $source;
        }, $hitList);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildKeywordQuery(string $query): array
    {
        return [
            'query' => [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['title^2', 'body', 'tags'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function buildVectorQuery(string $query, array $options): array
    {
        $embedding = $options['embedding'] ?? [];

        if ([] === $embedding) {
            return $this->buildKeywordQuery($query);
        }

        return [
            'query' => [
                'knn' => [
                    'embedding' => [
                        'vector' => $embedding,
                        'k' => 10,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function buildHybridQuery(string $query, array $options): array
    {
        $embedding = $options['embedding'] ?? [];

        if ([] === $embedding) {
            return $this->buildKeywordQuery($query);
        }

        return [
            'query' => [
                'bool' => [
                    'should' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['title^2', 'body', 'tags'],
                                'boost' => 0.5,
                            ],
                        ],
                        [
                            'knn' => [
                                'embedding' => [
                                    'vector' => $embedding,
                                    'k' => 10,
                                    'boost' => 0.5,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
