<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\OpenSearch\KnowledgeRepository;
use App\Service\EmbeddingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeRepository $repository,
        private readonly EmbeddingService $embeddingService,
    ) {
    }

    #[Route('/api/v1/knowledge/search', name: 'api_knowledge_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = (string) $request->query->get('q', '');
        $mode = (string) $request->query->get('mode', 'hybrid');
        $size = min(50, max(1, (int) $request->query->get('size', '10')));

        if ('' === $query) {
            return $this->json(['error' => 'Query parameter q is required'], 400);
        }

        $options = ['size' => $size];

        if (\in_array($mode, ['hybrid', 'vector'], true)) {
            $options['embedding'] = $this->embeddingService->embed($query);
        }

        $results = $this->repository->search($query, $mode, $options);

        return $this->json([
            'query' => $query,
            'mode' => $mode,
            'total' => \count($results),
            'results' => $results,
        ]);
    }
}
