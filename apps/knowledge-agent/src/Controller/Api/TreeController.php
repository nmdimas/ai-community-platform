<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\KnowledgeTreeBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TreeController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeTreeBuilder $treeBuilder,
    ) {
    }

    #[Route('/api/v1/knowledge/tree', name: 'api_knowledge_tree', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $tree = $this->treeBuilder->build();

        $response = new JsonResponse(['tree' => $tree]);
        $response->setMaxAge(60);
        $response->setPublic();

        return $response;
    }
}
