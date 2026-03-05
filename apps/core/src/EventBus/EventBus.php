<?php

declare(strict_types=1);

namespace App\EventBus;

use App\AgentRegistry\AgentRegistryInterface;

final class EventBus
{
    public function __construct(private readonly AgentRegistryInterface $registry)
    {
    }

    /**
     * Dispatch a platform event to all enabled agents subscribed to it.
     *
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $eventType, array $payload): void
    {
        $enabledAgents = $this->registry->findEnabled();

        foreach ($enabledAgents as $agent) {
            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'])
                ? json_decode($agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : $agent['manifest'];

            $subscribedEvents = (array) ($manifest['events'] ?? []);

            if (!in_array($eventType, $subscribedEvents, true)) {
                continue;
            }

            // A2A call will be implemented when Telegram Adapter is added
        }
    }
}
