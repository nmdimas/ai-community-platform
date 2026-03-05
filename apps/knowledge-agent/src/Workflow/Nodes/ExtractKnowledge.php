<?php

declare(strict_types=1);

namespace App\Workflow\Nodes;

use App\Event\AnalysisCompleteEvent;
use App\Event\ExtractionCompleteEvent;
use App\Workflow\DTO\ExtractedKnowledge;
use App\Workflow\KnowledgeExtractionAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

final class ExtractKnowledge extends Node
{
    public function __construct(
        private readonly KnowledgeExtractionAgent $agent,
    ) {
    }

    public function __invoke(AnalysisCompleteEvent $event, WorkflowState $state): ExtractionCompleteEvent
    {
        /** @var list<array<string, mixed>> $messages */
        $messages = $state->get('messages') ?? [];
        $messagesText = $this->formatMessages($messages);

        /** @var ExtractedKnowledge $knowledge */
        $knowledge = $this->agent->structured(
            new UserMessage("Extract structured knowledge from this message batch:\n\n{$messagesText}"),
            ExtractedKnowledge::class,
        );

        $state->set('knowledge', [
            'title' => $knowledge->title,
            'body' => $knowledge->body,
            'tags' => $knowledge->tags,
            'category' => $knowledge->category,
            'tree_path' => $knowledge->treePath,
        ]);

        return new ExtractionCompleteEvent();
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    private function formatMessages(array $messages): string
    {
        $lines = [];
        foreach ($messages as $msg) {
            $from = $msg['from'] ?? $msg['username'] ?? 'Unknown';
            $text = $msg['text'] ?? $msg['message'] ?? '';
            $lines[] = "[{$from}]: {$text}";
        }

        return implode("\n", $lines);
    }
}
