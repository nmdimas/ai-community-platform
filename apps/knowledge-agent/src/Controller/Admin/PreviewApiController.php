<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Workflow\KnowledgeExtractionAgent;
use App\Workflow\KnowledgeExtractionWorkflow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreviewApiController extends AbstractController
{
    public function __construct(
        private readonly KnowledgeExtractionAgent $agent,
    ) {
    }

    #[Route('/admin/knowledge/api/preview', name: 'admin_knowledge_api_preview', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed>|null $data */
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data) || !isset($data['messages']) || !\is_array($data['messages'])) {
            return $this->json(
                ['error' => 'Field "messages" is required and must be an array'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ([] === $data['messages']) {
            return $this->json(
                ['error' => 'Messages array must not be empty'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        /** @var list<array<string, mixed>> $messages */
        $messages = array_values($data['messages']);

        $workflow = new KnowledgeExtractionWorkflow(
            $this->agent,
            $messages,
            ['preview' => true],
        );

        $workflow->run();

        $knowledge = $workflow->getKnowledge();

        if (null === $knowledge) {
            return $this->json([
                'valuable' => false,
                'reason' => 'Повідомлення не містять корисних знань для витягування',
            ]);
        }

        return $this->json([
            'valuable' => true,
            'title' => $knowledge['title'] ?? '',
            'body' => $knowledge['body'] ?? '',
            'tags' => $knowledge['tags'] ?? [],
            'category' => $knowledge['category'] ?? '',
            'tree_path' => $knowledge['tree_path'] ?? '',
        ]);
    }
}
