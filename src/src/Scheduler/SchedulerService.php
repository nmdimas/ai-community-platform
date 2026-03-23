<?php

declare(strict_types=1);

namespace App\Scheduler;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class SchedulerService
{
    public function __construct(
        private readonly ScheduledJobRepositoryInterface $repository,
        private readonly CronExpressionHelperInterface $cronHelper,
        private readonly AsyncA2ADispatcherInterface $asyncDispatcher,
        private readonly LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly SchedulerJobLogRepositoryInterface $jobLog,
    ) {
    }

    public function tick(): int
    {
        // === Phase 1: Transactional — find due jobs, log starts, advance next_run_at, commit ===
        $this->connection->beginTransaction();

        try {
            $jobs = $this->repository->findDueJobs();
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }

        if ([] === $jobs) {
            $this->connection->commit();

            return 0;
        }

        /** @var list<array{id: string, skill_id: string, payload: array<string, mixed>, trace_id: string, request_id: string, log_id: string, cron_expression: ?string, timezone: string, retry_count: int, max_retries: int, retry_delay_seconds: int, agent_name: string, job_name: string}> $dispatchBatch */
        $dispatchBatch = [];

        foreach ($jobs as $job) {
            $id = (string) $job['id'];
            $skillId = (string) $job['skill_id'];
            $payload = is_string($job['payload'])
                ? (array) json_decode($job['payload'], true)
                : (array) ($job['payload'] ?? []);
            $cronExpression = isset($job['cron_expression']) ? (string) $job['cron_expression'] : null;
            $timezone = (string) ($job['timezone'] ?? 'UTC');

            $traceId = bin2hex(random_bytes(16));
            $requestId = bin2hex(random_bytes(8));

            $logId = $this->jobLog->logStart($id, (string) $job['agent_name'], $skillId, (string) $job['job_name'], $payload);

            // Compute and update next_run_at BEFORE dispatch (crash-safe)
            $nextRunAt = null !== $cronExpression
                ? $this->cronHelper->computeNextRun($cronExpression, $timezone)->format('Y-m-d H:i:sP')
                : null;

            $this->repository->updateAfterRun($id, 'running', $nextRunAt);

            $dispatchBatch[] = [
                'id' => $id,
                'skill_id' => $skillId,
                'payload' => $payload,
                'trace_id' => $traceId,
                'request_id' => $requestId,
                'log_id' => $logId,
                'cron_expression' => $cronExpression,
                'timezone' => $timezone,
                'retry_count' => (int) ($job['retry_count'] ?? 0),
                'max_retries' => (int) ($job['max_retries'] ?? 3),
                'retry_delay_seconds' => (int) ($job['retry_delay_seconds'] ?? 60),
                'agent_name' => (string) $job['agent_name'],
                'job_name' => (string) $job['job_name'],
            ];
        }

        $this->connection->commit();

        // === Phase 2: Async dispatch — all A2A calls in parallel ===
        $results = $this->asyncDispatcher->dispatchAll($dispatchBatch);

        // === Phase 3: Process results — log finishes, handle retries ===
        foreach ($dispatchBatch as $entry) {
            $id = $entry['id'];
            $logId = $entry['log_id'];
            $result = $results[$id] ?? ['status' => 'failed', 'error' => 'No dispatch result'];

            if ('completed' === $result['status']) {
                $this->jobLog->logFinish($logId, 'completed', null, $result['result'] ?? []);
                $this->repository->updateAfterRun($id, 'completed', null);

                $this->logger->info('Scheduled job executed', [
                    'job_id' => $id,
                    'agent' => $entry['agent_name'],
                    'job' => $entry['job_name'],
                    'skill' => $entry['skill_id'],
                ]);
            } else {
                $errorMsg = (string) ($result['error'] ?? 'Unknown error');
                $this->jobLog->logFinish($logId, 'failed', $errorMsg, $result['result'] ?? null);
                $this->handleFailure(
                    $id,
                    $entry['retry_count'],
                    $entry['max_retries'],
                    $entry['retry_delay_seconds'],
                    ['agent_name' => $entry['agent_name'], 'job_name' => $entry['job_name']],
                );
            }
        }

        return count($dispatchBatch);
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public function registerFromManifest(string $agentName, array $manifest): int
    {
        $scheduledJobs = $manifest['scheduled_jobs'] ?? [];
        if (!is_array($scheduledJobs) || [] === $scheduledJobs) {
            return 0;
        }

        $registered = 0;

        foreach ($scheduledJobs as $job) {
            if (!is_array($job)) {
                continue;
            }

            $jobName = (string) ($job['name'] ?? '');
            $skillId = (string) ($job['skill_id'] ?? '');
            $cronExpression = isset($job['cron_expression']) ? (string) $job['cron_expression'] : null;
            $payload = is_array($job['payload'] ?? null) ? $job['payload'] : [];
            $maxRetries = (int) ($job['max_retries'] ?? 3);
            $retryDelaySeconds = (int) ($job['retry_delay_seconds'] ?? 60);
            $timezone = (string) ($job['timezone'] ?? 'UTC');

            if ('' === $jobName || '' === $skillId) {
                continue;
            }

            $nextRunAt = null !== $cronExpression
                ? $this->cronHelper->computeNextRun($cronExpression, $timezone)->format('Y-m-d H:i:sP')
                : (new \DateTimeImmutable('now'))->format('Y-m-d H:i:sP');

            $this->repository->registerJob(
                $agentName,
                $jobName,
                $skillId,
                $payload,
                $cronExpression,
                $maxRetries,
                $retryDelaySeconds,
                $timezone,
                $nextRunAt,
            );

            ++$registered;
        }

        return $registered;
    }

    public function removeByAgent(string $agentName): int
    {
        return $this->repository->deleteByAgent($agentName);
    }

    public function enableByAgent(string $agentName): int
    {
        $count = $this->repository->enableByAgent($agentName);

        if ($count > 0) {
            // Recompute next_run_at for all jobs of this agent
            $jobs = $this->repository->findByAgent($agentName);
            foreach ($jobs as $job) {
                $cronExpression = isset($job['cron_expression']) ? (string) $job['cron_expression'] : null;
                if (null === $cronExpression) {
                    continue;
                }
                $timezone = (string) ($job['timezone'] ?? 'UTC');
                $nextRunAt = $this->cronHelper->computeNextRun($cronExpression, $timezone)->format('Y-m-d H:i:sP');
                $this->repository->updateAfterRun((string) $job['id'], (string) ($job['last_status'] ?? 'pending'), $nextRunAt);
            }
        }

        return $count;
    }

    public function disableByAgent(string $agentName): int
    {
        return $this->repository->disableByAgent($agentName);
    }

    /**
     * @param array<string, mixed> $job
     */
    private function handleFailure(
        string $id,
        int $retryCount,
        int $maxRetries,
        int $retryDelaySeconds,
        array $job,
        ?\Throwable $exception = null,
    ): void {
        $newRetryCount = $retryCount + 1;

        if ($newRetryCount >= $maxRetries) {
            $this->repository->disableJob($id);
            $this->logger->warning('Scheduled job dead-lettered after max retries', [
                'job_id' => $id,
                'agent' => $job['agent_name'],
                'job' => $job['job_name'],
                'retry_count' => $newRetryCount,
                'max_retries' => $maxRetries,
                'exception' => $exception?->getMessage(),
            ]);
        } else {
            $nextRetryAt = (new \DateTimeImmutable('now'))
                ->modify(sprintf('+%d seconds', $retryDelaySeconds))
                ->format('Y-m-d H:i:sP');

            $this->repository->updateRetry($id, $newRetryCount, $nextRetryAt);
            $this->logger->info('Scheduled job failed, will retry', [
                'job_id' => $id,
                'agent' => $job['agent_name'],
                'job' => $job['job_name'],
                'retry_count' => $newRetryCount,
                'next_retry_at' => $nextRetryAt,
                'exception' => $exception?->getMessage(),
            ]);
        }
    }
}
