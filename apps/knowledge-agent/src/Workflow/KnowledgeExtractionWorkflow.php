<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Workflow\Nodes\AnalyzeMessages;
use App\Workflow\Nodes\EnrichMetadata;
use App\Workflow\Nodes\ExtractKnowledge;
use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;

final class KnowledgeExtractionWorkflow extends Workflow
{
    private WorkflowState $workflowState;

    /**
     * @param list<array<string, mixed>> $messages
     * @param array<string, mixed>       $chunkMeta
     */
    public function __construct(
        private readonly KnowledgeExtractionAgent $agent,
        array $messages,
        array $chunkMeta = [],
    ) {
        $this->workflowState = new WorkflowState([
            'messages' => $messages,
            'chunk_meta' => $chunkMeta,
            'is_valuable' => false,
            'knowledge' => null,
        ]);

        parent::__construct(null, null, $this->workflowState);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getKnowledge(): ?array
    {
        /** @var array<string, mixed>|null $knowledge */
        $knowledge = $this->workflowState->get('knowledge');

        return $knowledge;
    }

    /**
     * @return list<NodeInterface>
     */
    protected function nodes(): array
    {
        return [
            new AnalyzeMessages($this->agent),
            new ExtractKnowledge($this->agent),
            new EnrichMetadata(),
        ];
    }
}
