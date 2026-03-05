<?php

declare(strict_types=1);

namespace App\Controller\Api\OpenClaw;

use App\AgentDiscovery\AgentInvokeBridge;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InvokeController extends AbstractController
{
    public function __construct(
        private readonly AgentInvokeBridge $bridge,
        private readonly string $gatewayToken,
    ) {
    }

    #[Route('/api/v1/agents/invoke', name: 'api_openclaw_invoke', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            /** @var array<string, mixed> $body */
            $body = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $tool = (string) ($body['tool'] ?? '');
        if ('' === $tool) {
            return $this->json(['error' => 'tool is required'], Response::HTTP_BAD_REQUEST);
        }

        /** @var array<string, mixed> $input */
        $input = (array) ($body['input'] ?? []);
        $traceId = (string) ($body['trace_id'] ?? uniqid('trace_', true));
        $requestId = (string) ($body['request_id'] ?? uniqid('req_', true));

        $result = $this->bridge->invoke($tool, $input, $traceId, $requestId);

        return $this->json($result);
    }

    private function isAuthorized(Request $request): bool
    {
        if ('' === $this->gatewayToken) {
            return false;
        }

        $header = $request->headers->get('Authorization', '');

        return 'Bearer '.$this->gatewayToken === $header;
    }
}
