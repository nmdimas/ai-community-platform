<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\AgentRegistry\AgentRegistryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AgentListController extends AbstractController
{
    public function __construct(private readonly AgentRegistryRepository $registry)
    {
    }

    #[Route('/api/v1/internal/agents', name: 'api_internal_agents_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $agents = $this->registry->findAll();

        return $this->json(['agents' => $agents]);
    }
}
