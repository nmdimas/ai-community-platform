<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

final class MigrateController extends AbstractController
{
    public function __construct(
        private readonly string $internalToken,
        private readonly string $projectDir,
    ) {
    }

    #[Route('/api/v1/internal/migrate', name: 'api_internal_migrate', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-Platform-Internal-Token');

        if ($token !== $this->internalToken) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $process = new Process(
                ['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction', '--allow-no-migration'],
                $this->projectDir,
            );
            $process->setTimeout(60);
            $process->mustRun();

            return $this->json([
                'status' => 'ok',
                'output' => $process->getOutput(),
            ]);
        } catch (ProcessFailedException $e) {
            return $this->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
