<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\AgentRegistry\AgentRegistryAuditLogger;
use App\AgentRegistry\AgentRegistryRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AgentConfigController extends AbstractController
{
    public function __construct(
        private readonly AgentRegistryRepository $registry,
        private readonly AgentRegistryAuditLogger $audit,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/api/v1/internal/agents/{name}/config', name: 'api_internal_agents_config', methods: ['PUT'])]
    public function __invoke(string $name, Request $request): JsonResponse
    {
        $agent = $this->registry->findByName($name);

        if (null === $agent) {
            return $this->json(['error' => sprintf('Agent "%s" not found', $name)], Response::HTTP_NOT_FOUND);
        }

        $content = $request->getContent();
        if ('' === $content) {
            return $this->json(['error' => 'Empty request body'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            /** @var array<string, mixed> $config */
            $config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->logger->warning('AgentConfigController is deprecated. Agents should manage their own config in their own storage.', ['agent' => $name]);

        $this->registry->updateConfig($name, $config);

        $actor = $this->getUser()?->getUserIdentifier() ?? 'unknown';
        $this->audit->log($name, 'config_updated', $actor, $config);

        return $this->json(['status' => 'updated', 'name' => $name]);
    }
}
