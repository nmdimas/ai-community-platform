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

final class EntriesController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeRepository $repository,
        private readonly EmbeddingService $embeddingService,
        private readonly string $internalToken,
    ) {
    }

    #[Route('/api/v1/knowledge/entries', name: 'api_knowledge_entries_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $from = max(0, (int) $request->query->get('from', '0'));
        $size = min(100, max(1, (int) $request->query->get('size', '20')));

        $filters = [];
        if ($request->query->has('tree_path')) {
            $filters['tree_path'] = (string) $request->query->get('tree_path');
        }
        if ($request->query->has('category')) {
            $filters['category'] = (string) $request->query->get('category');
        }
        if ($request->query->has('tags')) {
            $filters['tags'] = explode(',', (string) $request->query->get('tags'));
        }

        $entries = $this->repository->listEntries($filters, $from, $size);

        return $this->json(['entries' => $entries, 'count' => \count($entries), 'from' => $from, 'size' => $size]);
    }

    #[Route('/api/v1/knowledge/entries', name: 'api_knowledge_entries_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-Platform-Internal-Token');
        if ($token !== $this->internalToken) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data) || !isset($data['title']) || !isset($data['body'])) {
            return $this->json(['error' => 'title and body are required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (isset($data['title']) && \is_string($data['title'])) {
            $embeddingText = $data['title'].' '.($data['body'] ?? '');
            $data['embedding'] = $this->embeddingService->embed($embeddingText);
        }

        $id = $this->repository->index($data);

        return $this->json(['id' => $id, 'status' => 'created'], Response::HTTP_CREATED);
    }
}
