<?php

declare(strict_types=1);

namespace App\AgentDiscovery;

use App\AgentRegistry\AgentRegistryInterface;
use Doctrine\DBAL\Connection;

final class AgentInvokeBridge
{
    public function __construct(
        private readonly AgentRegistryInterface $registry,
        private readonly Connection $dbal,
    ) {
    }

    /**
     * Invoke an agent tool via the A2A bridge.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function invoke(string $tool, array $input, string $traceId, string $requestId): array
    {
        foreach ($this->registry->findEnabled() as $agent) {
            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'])
                ? json_decode((string) $agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : $agent['manifest'];

            /** @var list<string> $capabilities */
            $capabilities = (array) ($manifest['capabilities'] ?? []);

            if (in_array($tool, $capabilities, true)) {
                return $this->callAgent($agent, $manifest, $tool, $input, $traceId, $requestId);
            }
        }

        // Check if tool exists on a disabled agent
        foreach ($this->registry->findAll() as $agent) {
            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'])
                ? json_decode((string) $agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : $agent['manifest'];

            /** @var list<string> $capabilities */
            $capabilities = (array) ($manifest['capabilities'] ?? []);

            if (in_array($tool, $capabilities, true)) {
                $this->auditLog($tool, (string) $agent['name'], $traceId, $requestId, 0, 'failed');

                return ['status' => 'failed', 'reason' => 'agent_disabled'];
            }
        }

        return ['status' => 'failed', 'reason' => 'unknown_tool'];
    }

    /**
     * @param array<string, mixed> $agent
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function callAgent(
        array $agent,
        array $manifest,
        string $tool,
        array $input,
        string $traceId,
        string $requestId,
    ): array {
        $a2aEndpoint = (string) ($manifest['a2a_endpoint'] ?? '');
        $agentName = (string) $agent['name'];

        if ('' === $a2aEndpoint) {
            $this->auditLog($tool, $agentName, $traceId, $requestId, 0, 'failed');

            return ['status' => 'failed', 'reason' => 'no_a2a_endpoint'];
        }

        $start = microtime(true);
        $payload = [
            'intent' => $tool,
            'payload' => $input,
            'request_id' => $requestId,
            'trace_id' => $traceId,
        ];

        try {
            $responseBody = $this->postJson($a2aEndpoint, $payload);
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            /** @var array<string, mixed> $result */
            $result = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            $status = (string) ($result['status'] ?? 'unknown');
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $start) * 1000);
            $status = 'failed';
            $result = ['status' => 'failed', 'reason' => 'a2a_error', 'error' => $e->getMessage()];
        }

        $this->auditLog($tool, $agentName, $traceId, $requestId, $durationMs, $status);

        return array_merge($result, [
            'agent' => $agentName,
            'tool' => $tool,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $url, array $payload): string
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nContent-Length: ".strlen($body),
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);

        set_error_handler(static fn (): bool => true);

        try {
            $result = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }

        if (false === $result) {
            throw new \RuntimeException("Failed to connect to A2A endpoint: {$url}");
        }

        return $result;
    }

    private function auditLog(
        string $tool,
        string $agent,
        string $traceId,
        string $requestId,
        int $durationMs,
        string $status,
    ): void {
        $this->dbal->executeStatement(
            <<<'SQL'
            INSERT INTO agent_invocation_audit
                (tool, agent, trace_id, request_id, duration_ms, status, actor, created_at)
            VALUES
                (:tool, :agent, :traceId, :requestId, :durationMs, :status, 'openclaw', now())
            SQL,
            [
                'tool' => $tool,
                'agent' => $agent,
                'traceId' => $traceId ?: null,
                'requestId' => $requestId ?: null,
                'durationMs' => $durationMs,
                'status' => $status,
            ],
        );
    }
}
