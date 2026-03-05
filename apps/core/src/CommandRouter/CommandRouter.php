<?php

declare(strict_types=1);

namespace App\CommandRouter;

use App\AgentRegistry\AgentRegistryInterface;

final class CommandRouter
{
    public function __construct(private readonly AgentRegistryInterface $registry)
    {
    }

    /**
     * Resolve which enabled agent handles the given command.
     *
     * @return array<string, mixed>|null agent row, or null if command is unavailable
     */
    public function resolve(string $command): ?array
    {
        foreach ($this->registry->findEnabled() as $agent) {
            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'])
                ? json_decode($agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : $agent['manifest'];

            $commands = (array) ($manifest['commands'] ?? []);

            if (in_array($command, $commands, true)) {
                return $agent;
            }
        }

        return null;
    }

    /**
     * Human-readable "unavailable" response for unknown/disabled commands.
     */
    public function unavailableResponse(string $command): string
    {
        return sprintf('Команда "%s" недоступна.', $command);
    }
}
