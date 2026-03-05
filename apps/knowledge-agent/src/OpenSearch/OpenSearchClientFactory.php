<?php

declare(strict_types=1);

namespace App\OpenSearch;

use OpenSearch\Client;
use OpenSearch\ClientBuilder;

final class OpenSearchClientFactory
{
    public function __construct(
        private readonly string $opensearchUrl,
    ) {
    }

    public function create(): Client
    {
        return ClientBuilder::create()
            ->setHosts([$this->opensearchUrl])
            ->build();
    }
}
