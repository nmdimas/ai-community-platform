<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\RabbitMQ\RabbitMQPublisher;
use App\Service\MessageChunker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UploadController extends AbstractController
{
    public function __construct(
        private readonly MessageChunker $chunker,
        private readonly RabbitMQPublisher $publisher,
        private readonly string $internalToken,
    ) {
    }

    #[Route('/api/v1/knowledge/upload', name: 'api_knowledge_upload', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-Platform-Internal-Token');
        if ($token !== $this->internalToken) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data) || !isset($data['messages']) || !\is_array($data['messages'])) {
            return $this->json(['error' => 'Invalid payload: messages array required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var list<array<string, mixed>> $messages */
        $messages = $data['messages'];

        if ([] === $messages) {
            return $this->json(['error' => 'No messages provided'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var array<string, mixed> $meta */
        $meta = $data['meta'] ?? [];

        $chunks = $this->chunker->chunk($messages);

        foreach ($chunks as $chunk) {
            $chunk['meta'] = $meta;
            $this->publisher->publishChunk($chunk);
        }

        return $this->json([
            'status' => 'queued',
            'chunks' => \count($chunks),
        ], Response::HTTP_ACCEPTED);
    }
}
