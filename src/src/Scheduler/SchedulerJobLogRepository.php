<?php

declare(strict_types=1);

namespace App\Scheduler;

use Doctrine\DBAL\Connection;

final class SchedulerJobLogRepository implements SchedulerJobLogRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function logStart(string $jobId, string $agentName, string $skillId, string $jobName, array $payload): string
    {
        $id = $this->connection->fetchOne(
            <<<'SQL'
            INSERT INTO scheduler_job_logs (job_id, agent_name, skill_id, job_name, payload_sent, status, started_at)
            VALUES (:jobId, :agentName, :skillId, :jobName, :payload, 'running', now())
            RETURNING id
            SQL,
            [
                'jobId' => $jobId,
                'agentName' => $agentName,
                'skillId' => $skillId,
                'jobName' => $jobName,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ],
        );

        return (string) $id;
    }

    /**
     * @param array<string, mixed>|null $response
     */
    public function logFinish(string $logId, string $status, ?string $errorMessage = null, ?array $response = null): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
            UPDATE scheduler_job_logs
            SET status = :status,
                error_message = :errorMessage,
                response_received = :response,
                finished_at = now()
            WHERE id = :id
            SQL,
            [
                'id' => $logId,
                'status' => $status,
                'errorMessage' => $errorMessage,
                'response' => null !== $response ? json_encode($response, JSON_THROW_ON_ERROR) : null,
            ],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findByJob(string $jobId, int $limit = 50, int $offset = 0): array
    {
        /* @var list<array<string, mixed>> */
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM scheduler_job_logs WHERE job_id = :jobId ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            ['jobId' => $jobId, 'limit' => $limit, 'offset' => $offset],
            ['limit' => \Doctrine\DBAL\ParameterType::INTEGER, 'offset' => \Doctrine\DBAL\ParameterType::INTEGER],
        );
    }

    public function countByJob(string $jobId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM scheduler_job_logs WHERE job_id = :jobId',
            ['jobId' => $jobId],
        );
    }
}
