<?php

declare(strict_types=1);

namespace App\Workflow\DTO;

use NeuronAI\StructuredOutput\SchemaProperty;

final class ExtractedKnowledge
{
    #[SchemaProperty(description: 'Concise title of the knowledge entry (Ukrainian)')]
    public string $title;

    #[SchemaProperty(description: 'Full body text of the knowledge entry in Markdown format (Ukrainian)')]
    public string $body;

    /** @var list<string> */
    #[SchemaProperty(description: 'Relevant keyword tags for the entry')]
    public array $tags;

    #[SchemaProperty(description: 'Primary category of the knowledge (e.g. Technology, Business, Community)')]
    public string $category;

    #[SchemaProperty(description: 'Hierarchical path in the knowledge tree, slash-separated (e.g. Technology/PHP/Symfony)')]
    public string $treePath;
}
