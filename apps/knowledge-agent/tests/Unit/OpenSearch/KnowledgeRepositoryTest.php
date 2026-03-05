<?php

declare(strict_types=1);

namespace App\Tests\Unit\OpenSearch;

use App\OpenSearch\KnowledgeRepository;
use Codeception\Test\Unit;
use OpenSearch\Client;
use PHPUnit\Framework\MockObject\MockObject;

final class KnowledgeRepositoryTest extends Unit
{
    private Client&MockObject $client;
    private KnowledgeRepository $repository;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->repository = new KnowledgeRepository($this->client, 'knowledge_entries_v1');
    }

    public function testIndexReturnsId(): void
    {
        $this->client->expects($this->once())
            ->method('index')
            ->willReturn(['_id' => 'abc123']);

        $id = $this->repository->index(['title' => 'Test', 'body' => 'Content']);
        $this->assertSame('abc123', $id);
    }

    public function testGetReturnsNullWhenNotFound(): void
    {
        $this->client->method('get')->willReturn(['found' => false]);

        $result = $this->repository->get('nonexistent');
        $this->assertNull($result);
    }

    public function testGetReturnsEntryWhenFound(): void
    {
        $this->client->method('get')->willReturn([
            'found' => true,
            '_id' => 'abc123',
            '_source' => ['title' => 'Test', 'body' => 'Content'],
        ]);

        $result = $this->repository->get('abc123');
        $this->assertNotNull($result);
        $this->assertSame('abc123', $result['id']);
        $this->assertSame('Test', $result['title']);
    }

    public function testDeleteReturnsFalseOnException(): void
    {
        $this->client->method('delete')->willThrowException(new \RuntimeException('Not found'));

        $result = $this->repository->delete('nonexistent');
        $this->assertFalse($result);
    }

    public function testSearchReturnsFormattedResults(): void
    {
        $this->client->method('search')->willReturn([
            'hits' => [
                'hits' => [
                    ['_id' => '1', '_source' => ['title' => 'Result 1']],
                    ['_id' => '2', '_source' => ['title' => 'Result 2']],
                ],
            ],
        ]);

        $results = $this->repository->search('test', 'keyword');
        $this->assertCount(2, $results);
        $this->assertSame('1', $results[0]['id']);
        $this->assertSame('2', $results[1]['id']);
    }
}
