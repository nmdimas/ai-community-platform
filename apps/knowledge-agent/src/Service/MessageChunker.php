<?php

declare(strict_types=1);

namespace App\Service;

final class MessageChunker
{
    private const TIME_WINDOW_MINUTES = 15;
    private const MAX_MESSAGES = 50;
    private const OVERLAP = 5;

    /**
     * @param list<array<string, mixed>> $messages
     *
     * @return list<array<string, mixed>>
     */
    public function chunk(array $messages): array
    {
        if ([] === $messages) {
            return [];
        }

        $chunks = [];
        $current = [];

        foreach ($messages as $message) {
            if ([] === $current) {
                $current[] = $message;
                continue;
            }

            $firstTs = $this->getTimestamp($current[0]);
            $currentTs = $this->getTimestamp($message);
            $minutesDiff = ($currentTs - $firstTs) / 60;

            if ($minutesDiff > self::TIME_WINDOW_MINUTES || \count($current) >= self::MAX_MESSAGES) {
                $chunks[] = $this->buildChunk($current);

                // Keep last OVERLAP messages for next chunk
                $current = \array_slice($current, -self::OVERLAP);
            }

            $current[] = $message;
        }

        $chunks[] = $this->buildChunk($current);

        return $chunks;
    }

    /**
     * @param list<array<string, mixed>> $messages
     *
     * @return array<string, mixed>
     */
    private function buildChunk(array $messages): array
    {
        $ids = array_map(static fn (array $m): string => (string) $m['id'], $messages);
        sort($ids);

        return [
            'messages' => $messages,
            'chunk_hash' => hash('sha256', json_encode($ids, \JSON_THROW_ON_ERROR)),
            'message_count' => \count($messages),
        ];
    }

    /**
     * @param array<string, mixed> $message
     */
    private function getTimestamp(array $message): int
    {
        $ts = $message['timestamp'] ?? $message['date'] ?? 0;

        if (\is_string($ts)) {
            return (int) strtotime($ts);
        }

        return (int) $ts;
    }
}
