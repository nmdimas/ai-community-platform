<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\OpenSearch\KnowledgeRepository;
use App\Service\EmbeddingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EntryController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeRepository $repository,
        private readonly EmbeddingService $embeddingService,
        private readonly string $internalToken,
    ) {
    }

    #[Route('/api/v1/knowledge/entries/{id}', name: 'api_knowledge_entry_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $entry = $this->repository->get($id);

        if (null === $entry) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['entry' => $entry]);
    }

    #[Route('/api/v1/knowledge/entries/{id}', name: 'api_knowledge_entry_update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-Platform-Internal-Token');
        if ($token !== $this->internalToken) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid payload'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Regenerate embedding if content changed
        if (isset($data['title']) || isset($data['body'])) {
            $existing = $this->repository->get($id);
            if (null !== $existing) {
                $title = $data['title'] ?? $existing['title'] ?? '';
                $body = $data['body'] ?? $existing['body'] ?? '';
                $data['embedding'] = $this->embeddingService->embed("{$title} {$body}");
            }
        }

        $updated = $this->repository->update($id, $data);

        if (!$updated) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['status' => 'updated', 'id' => $id]);
    }

    #[Route('/api/v1/knowledge/entries/{id}', name: 'api_knowledge_entry_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        $token = $request->headers->get('X-Platform-Internal-Token');
        if ($token !== $this->internalToken) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['status' => 'deleted'], Response::HTTP_NO_CONTENT);
    }
}
