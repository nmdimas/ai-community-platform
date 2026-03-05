<?php

declare(strict_types=1);

namespace App\Controller\Api\OpenClaw;

use App\AgentDiscovery\DiscoveryBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DiscoveryController extends AbstractController
{
    private const CACHE_KEY = 'openclaw_discovery_payload';
    private const CACHE_TTL = 30;

    public function __construct(
        private readonly DiscoveryBuilder $discoveryBuilder,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $gatewayToken,
    ) {
    }

    #[Route('/api/v1/agents/discovery', name: 'api_openclaw_discovery', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $item = $this->cache->getItem(self::CACHE_KEY);

        if ($item->isHit()) {
            /** @var array<string, mixed> $cached */
            $cached = $item->get();

            return $this->json($cached);
        }

        $payload = $this->discoveryBuilder->build();

        $item->set($payload);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);

        return $this->json($payload);
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
