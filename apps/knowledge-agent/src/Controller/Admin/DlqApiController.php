<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\RabbitMQ\RabbitMQPublisher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DlqApiController extends AbstractController
{
    public function __construct(
        private readonly RabbitMQPublisher $publisher,
    ) {
    }

    #[Route('/admin/knowledge/api/dlq', name: 'admin_knowledge_api_dlq_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        return $this->json(['count' => $this->publisher->getDlqCount()]);
    }

    #[Route('/admin/knowledge/api/dlq/requeue', name: 'admin_knowledge_api_dlq_requeue', methods: ['POST'])]
    public function requeue(): JsonResponse
    {
        $requeued = $this->publisher->requeueDlq();

        return $this->json(['requeued' => $requeued]);
    }
}
