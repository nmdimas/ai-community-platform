<?php

declare(strict_types=1);

namespace App\OpenSearch;

use OpenSearch\Client;

final class OpenSearchIndexManager
{
    public function __construct(
        private readonly Client $client,
        private readonly string $indexName,
    ) {
    }

    public function indexExists(): bool
    {
        return $this->client->indices()->exists(['index' => $this->indexName]);
    }

    public function createIndex(): void
    {
        if ($this->indexExists()) {
            return;
        }

        $this->client->indices()->create([
            'index' => $this->indexName,
            'body' => [
                'settings' => [
                    'index' => [
                        'knn' => true,
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                    ],
                    'analysis' => [
                        'analyzer' => [
                            'ukrainian' => [
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'stop'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'title' => [
                            'type' => 'text',
                            'analyzer' => 'ukrainian',
                        ],
                        'body' => [
                            'type' => 'text',
                            'analyzer' => 'ukrainian',
                        ],
                        'tags' => ['type' => 'keyword'],
                        'category' => ['type' => 'keyword'],
                        'tree_path' => ['type' => 'keyword'],
                        'embedding' => [
                            'type' => 'knn_vector',
                            'dimension' => 1536,
                            'method' => [
                                'name' => 'hnsw',
                                'space_type' => 'cosinesimil',
                                'engine' => 'nmslib',
                            ],
                        ],
                        'source_message_ids' => ['type' => 'keyword'],
                        'message_link' => ['type' => 'keyword'],
                        'created_by' => ['type' => 'keyword'],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                    ],
                ],
            ],
        ]);
    }

    public function deleteIndex(): void
    {
        if (!$this->indexExists()) {
            return;
        }

        $this->client->indices()->delete(['index' => $this->indexName]);
    }
}
