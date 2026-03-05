<?php

declare(strict_types=1);

namespace App\Workflow\Nodes;

use App\Event\AnalysisCompleteEvent;
use App\Workflow\DTO\AnalysisResult;
use App\Workflow\KnowledgeExtractionAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;

final class AnalyzeMessages extends Node
{
    public function __construct(
        private readonly KnowledgeExtractionAgent $agent,
    ) {
    }

    public function __invoke(StartEvent $event, WorkflowState $state): AnalysisCompleteEvent|StopEvent
    {
        /** @var list<array<string, mixed>> $messages */
        $messages = $state->get('messages') ?? [];

        if ([] === $messages) {
            return new StopEvent();
        }

        $messagesText = $this->formatMessages($messages);

        /** @var AnalysisResult $result */
        $result = $this->agent->structured(
            new UserMessage("Analyze this message batch and determine if it contains extractable knowledge:\n\n{$messagesText}"),
            AnalysisResult::class,
        );

        $state->set('is_valuable', $result->isValuable);
        $state->set('analysis_reason', $result->reason);

        if (!$result->isValuable) {
            return new StopEvent();
        }

        return new AnalysisCompleteEvent();
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
