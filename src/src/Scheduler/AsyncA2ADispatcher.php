<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\AgentRegistry\AgentRegistryInterface;
use App\AgentRegistry\ManifestValidator;
use App\Observability\TraceContext;
use Psr\Log\LoggerInterface;
use React\Http\Browser;
use React\Socket\Connector;

use function React\Async\await;
use function React\Promise\all;

final class AsyncA2ADispatcher implements AsyncA2ADispatcherInterface
{
    private readonly Browser $browser;

    public function __construct(
        private readonly AgentRegistryInterface $registry,
        private readonly LoggerInterface $logger,
        private readonly string $internalToken = '',
        private readonly int $concurrencyLimit = 20,
        float $timeout = 30.0,
    ) {
        $connector = new Connector(['timeout' => $timeout]);
        $this->browser = (new Browser($connector))->withTimeout($timeout);
    }

    /**
     * Dispatch all jobs concurrently via non-blocking HTTP.
     *
     * @param list<array{id: string, skill_id: string, payload: array<string, mixed>, trace_id: string, request_id: string}> $jobs
     *
     * @return array<string, array{status: string, result?: array<string, mixed>, error?: string}>
     */
    public function dispatchAll(array $jobs): array
    {
        if ([] === $jobs) {
            return [];
        }

        $agents = $this->buildAgentIndex();
        $promises = [];
        $results = [];
        $inFlight = 0;
        $queue = [];

        foreach ($jobs as $job) {
            $id = $job['id'];
            $resolved = $this->resolveAgent($agents, $job['skill_id']);

            if (null === $resolved) {
                $results[$id] = ['status' => 'failed', 'error' => 'unknown_skill: '.$job['skill_id']];

                continue;
            }

            [$endpoint, $agentName, $config] = $resolved;

            $buildPromise = function () use ($job, $endpoint, $agentName, $config, $id): \React\Promise\PromiseInterface {
                return $this->dispatchOne($endpoint, $agentName, $config, $job)
                    ->then(
                        fn (array $result): array => ['id' => $id, 'status' => 'completed', 'result' => $result],
                        fn (\Throwable $e): array => ['id' => $id, 'status' => 'failed', 'error' => $e->getMessage()],
                    );
            };

            if ($inFlight < $this->concurrencyLimit) {
                $promises[$id] = $buildPromise();
                ++$inFlight;
            } else {
                $queue[] = ['id' => $id, 'build' => $buildPromise];
            }
        }

        // Await all initial promises
        if ([] !== $promises) {
            /** @var array<string, array{id: string, status: string, result?: array<string, mixed>, error?: string}> $settled */
            $settled = await(all($promises));

            foreach ($settled as $entry) {
                $entryId = $entry['id'];
                unset($entry['id']);
                $results[$entryId] = $entry;
            }
        }

        // Process overflow queue in batches
        while ([] !== $queue) {
            $batch = array_splice($queue, 0, $this->concurrencyLimit);
            $batchPromises = [];

            foreach ($batch as $item) {
                $batchPromises[$item['id']] = ($item['build'])();
            }

            /** @var array<string, array{id: string, status: string, result?: array<string, mixed>, error?: string}> $settled */
            $settled = await(all($batchPromises));

            foreach ($settled as $entry) {
                $entryId = $entry['id'];
                unset($entry['id']);
                $results[$entryId] = $entry;
            }
        }

        return $results;
    }

    /**
     * @param array<string, mixed>                                                                                     $config
     * @param array{id: string, skill_id: string, payload: array<string, mixed>, trace_id: string, request_id: string} $job
     *
     * @return \React\Promise\PromiseInterface<array<string, mixed>>
     */
    private function dispatchOne(string $endpoint, string $agentName, array $config, array $job): \React\Promise\PromiseInterface
    {
        $agentRunId = 'run_'.bin2hex(random_bytes(8));

        $payload = [
            'intent' => $job['skill_id'],
            'payload' => $job['payload'],
            'request_id' => $job['request_id'],
            'trace_id' => $job['trace_id'],
            'agent_run_id' => $agentRunId,
            'hop' => 1,
        ];

        $systemPrompt = (string) ($config['system_prompt'] ?? '');
        if ('' !== $systemPrompt) {
            $payload['system_prompt'] = $systemPrompt;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'traceparent' => TraceContext::buildTraceparent($job['trace_id']),
            'x-request-id' => $job['request_id'],
            'x-agent-run-id' => $agentRunId,
            'x-a2a-hop' => '1',
        ];

        if ('' !== $this->internalToken) {
            $headers['X-Platform-Internal-Token'] = $this->internalToken;
        }

        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->logger->info('Async A2A dispatch', [
            'job_id' => $job['id'],
            'agent' => $agentName,
            'skill' => $job['skill_id'],
            'trace_id' => $job['trace_id'],
        ]);

        return $this->browser->post($endpoint, $headers, $body)
            ->then(function (\Psr\Http\Message\ResponseInterface $response) use ($job, $agentName): array {
                /** @var array<string, mixed> $result */
                $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

                $this->logger->info('Async A2A completed', [
                    'job_id' => $job['id'],
                    'agent' => $agentName,
                    'http_status' => $response->getStatusCode(),
                ]);

                return $result;
            });
    }

    /**
     * Build an index of enabled agents keyed by skill ID.
     *
     * @return array<string, array{endpoint: string, agent_name: string, config: array<string, mixed>}>
     */
    private function buildAgentIndex(): array
    {
        $index = [];

        foreach ($this->registry->findEnabled() as $agent) {
            /** @var array<string, mixed> $manifest */
            $manifest = is_string($agent['manifest'])
                ? json_decode((string) $agent['manifest'], true, 512, JSON_THROW_ON_ERROR)
                : $agent['manifest'];

            $endpoint = ManifestValidator::resolveUrl($manifest);
            if ('' === $endpoint) {
                continue;
            }

            /** @var array<string, mixed> $config */
            $config = is_string($agent['config'] ?? null)
                ? (array) json_decode((string) $agent['config'], true)
                : (array) ($agent['config'] ?? []);

            $skillIds = ManifestValidator::extractSkillIds($manifest);

            foreach ($skillIds as $skillId) {
                $index[$skillId] = [
                    'endpoint' => $endpoint,
                    'agent_name' => (string) $agent['name'],
                    'config' => $config,
                ];
            }
        }

        return $index;
    }

    /**
     * @param array<string, array{endpoint: string, agent_name: string, config: array<string, mixed>}> $agents
     *
     * @return array{string, string, array<string, mixed>}|null [endpoint, agentName, config]
     */
    private function resolveAgent(array $agents, string $skillId): ?array
    {
        if (!isset($agents[$skillId])) {
            $this->logger->warning('Async dispatch: skill not found on any enabled agent', ['skill_id' => $skillId]);

            return null;
        }

        $entry = $agents[$skillId];

        return [$entry['endpoint'], $entry['agent_name'], $entry['config']];
    }
}
