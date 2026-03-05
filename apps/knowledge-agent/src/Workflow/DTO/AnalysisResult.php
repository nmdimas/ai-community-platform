<?php

declare(strict_types=1);

namespace App\Workflow\DTO;

use NeuronAI\StructuredOutput\SchemaProperty;

final class AnalysisResult
{
    #[SchemaProperty(description: 'Whether this message chunk contains extractable knowledge worth indexing')]
    public bool $isValuable;

    #[SchemaProperty(description: 'Brief reason for the decision (1-2 sentences)')]
    public string $reason;
}
