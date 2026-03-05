<?php

declare(strict_types=1);

namespace App\AgentRegistry;

use Doctrine\DBAL\Connection;

final class AgentRegistryAuditLogger
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function log(string $agentName, string $action, ?string $actor = null, array $payload = []): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
            INSERT INTO agent_registry_audit (agent_name, action, actor, payload, created_at)
            VALUES (:agentName, :action, :actor, :payload, now())
            SQL,
            [
                'agentName' => $agentName,
                'action' => $action,
                'actor' => $actor,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ],
        );
    }
}
