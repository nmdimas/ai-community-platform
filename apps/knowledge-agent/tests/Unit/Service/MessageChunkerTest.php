<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\MessageChunker;
use Codeception\Test\Unit;

final class MessageChunkerTest extends Unit
{
    private MessageChunker $chunker;

    protected function setUp(): void
    {
        $this->chunker = new MessageChunker();
    }

    public function testEmptyMessagesReturnsEmptyChunks(): void
    {
        $chunks = $this->chunker->chunk([]);
        $this->assertSame([], $chunks);
    }

    public function testSingleMessageCreatesOneChunk(): void
    {
        $messages = [['id' => '1', 'text' => 'hello', 'timestamp' => time()]];
        $chunks = $this->chunker->chunk($messages);

        $this->assertCount(1, $chunks);
        $this->assertCount(1, $chunks[0]['messages']);
        $this->assertSame(1, $chunks[0]['message_count']);
        $this->assertNotEmpty($chunks[0]['chunk_hash']);
    }

    public function testChunkHashIsDeterministic(): void
    {
        $messages = [
            ['id' => '1', 'text' => 'hello', 'timestamp' => time()],
            ['id' => '2', 'text' => 'world', 'timestamp' => time()],
        ];

        $chunks1 = $this->chunker->chunk($messages);
        $chunks2 = $this->chunker->chunk($messages);

        $this->assertSame($chunks1[0]['chunk_hash'], $chunks2[0]['chunk_hash']);
    }

    public function testTimeWindowSplitsMessages(): void
    {
        $now = time();
        $messages = [
            ['id' => '1', 'text' => 'first batch', 'timestamp' => $now],
            ['id' => '2', 'text' => 'first batch', 'timestamp' => $now + 300],
            // More than 15 minutes later
            ['id' => '3', 'text' => 'second batch', 'timestamp' => $now + 1500],
        ];

        $chunks = $this->chunker->chunk($messages);
        $this->assertGreaterThanOrEqual(2, \count($chunks));
    }

    public function testMaxMessagesCapCreatesMultipleChunks(): void
    {
        $now = time();
        $messages = [];
        for ($i = 1; $i <= 55; ++$i) {
            $messages[] = ['id' => (string) $i, 'text' => "msg {$i}", 'timestamp' => $now + $i];
        }

        $chunks = $this->chunker->chunk($messages);
        $this->assertGreaterThan(1, \count($chunks));
    }

    public function testOverlapBetweenChunks(): void
    {
        $now = time();
        $messages = [];
        for ($i = 1; $i <= 55; ++$i) {
            $messages[] = ['id' => (string) $i, 'text' => "msg {$i}", 'timestamp' => $now + $i];
        }

        $chunks = $this->chunker->chunk($messages);
        $this->assertGreaterThan(1, \count($chunks));

        // Second chunk should start with messages that were in the first chunk (overlap)
        $firstChunkLastIds = \array_slice(
            array_map(static fn (array $m): string => (string) $m['id'], $chunks[0]['messages']),
            -5
        );
        $secondChunkFirstIds = \array_slice(
            array_map(static fn (array $m): string => (string) $m['id'], $chunks[1]['messages']),
            0,
            5
        );

        $this->assertSame($firstChunkLastIds, $secondChunkFirstIds);
    }
}
