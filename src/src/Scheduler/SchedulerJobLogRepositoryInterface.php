<?php

declare(strict_types=1);

namespace App\Scheduler;

interface SchedulerJobLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function logStart(string $jobId, string $agentName, string $skillId, string $jobName, array $payload): string;

    /**
     * @param array<string, mixed>|null $response
     */
    public function logFinish(string $logId, string $status, ?string $errorMessage = null, ?array $response = null): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function findByJob(string $jobId, int $limit = 50, int $offset = 0): array;

    public function countByJob(string $jobId): int;
}
