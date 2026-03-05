<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EmbeddingService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $litellmBaseUrl,
        private readonly string $litellmApiKey,
        private readonly string $embeddingModel,
    ) {
    }

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        $response = $this->httpClient->request('POST', $this->litellmBaseUrl.'/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->litellmApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->embeddingModel,
                'input' => $text,
            ],
        ]);

        /** @var array{data: list<array{embedding: list<float>}>} $data */
        $data = $response->toArray();

        return $data['data'][0]['embedding'] ?? [];
    }
}
