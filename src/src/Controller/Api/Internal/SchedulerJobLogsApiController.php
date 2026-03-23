<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Scheduler\SchedulerJobLogRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class SchedulerJobLogsApiController extends AbstractController
{
    public function __construct(
        private readonly SchedulerJobLogRepositoryInterface $logRepository,
    ) {
    }

    #[Route('/api/v1/internal/scheduler/{id}/logs', name: 'api_internal_scheduler_logs', requirements: ['id' => Requirement::UUID_V4], methods: ['GET'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 50)));
        $offset = ($page - 1) * $limit;

        $logs = $this->logRepository->findByJob($id, $limit, $offset);
        $total = $this->logRepository->countByJob($id);

        return $this->json([
            'job_id' => $id,
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
