<?php

declare(strict_types=1);

namespace App\Workflow\Nodes;

use App\Event\EnrichmentCompleteEvent;
use App\Event\ExtractionCompleteEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

final class EnrichMetadata extends Node
{
    public function __invoke(ExtractionCompleteEvent $event, WorkflowState $state): EnrichmentCompleteEvent|StopEvent
    {
        /** @var array<string, mixed>|null $knowledge */
        $knowledge = $state->get('knowledge');

        if (null === $knowledge) {
            return new StopEvent();
        }

        /** @var array<string, mixed> $chunkMeta */
        $chunkMeta = $state->get('chunk_meta') ?? [];

        $sourceMessageIds = $chunkMeta['message_ids'] ?? [];
        $chatId = $chunkMeta['chat_id'] ?? null;
        $firstMessageId = $sourceMessageIds[0] ?? null;

        $messageLink = null;
        if (null !== $chatId && null !== $firstMessageId) {
            $messageLink = "https://t.me/c/{$chatId}/{$firstMessageId}";
        }

        $knowledge['source_message_ids'] = $sourceMessageIds;
        $knowledge['message_link'] = $messageLink;
        $knowledge['created_by'] = $chunkMeta['created_by'] ?? 'system';
        $knowledge['source_type'] = 'telegram';

        $state->set('knowledge', $knowledge);

        return new EnrichmentCompleteEvent();
    }
}
