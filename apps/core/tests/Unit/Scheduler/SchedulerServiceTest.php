<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduler;

use App\Scheduler\AsyncA2ADispatcherInterface;
use App\Scheduler\CronExpressionHelperInterface;
use App\Scheduler\ScheduledJobRepositoryInterface;
use App\Scheduler\SchedulerJobLogRepositoryInterface;
use App\Scheduler\SchedulerService;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class SchedulerServiceTest extends Unit
{
    private ScheduledJobRepositoryInterface&MockObject $repository;
    private CronExpressionHelperInterface&MockObject $cronHelper;
    private AsyncA2ADispatcherInterface&MockObject $asyncDispatcher;
    private LoggerInterface&MockObject $logger;
    private Connection&MockObject $connection;
    private SchedulerJobLogRepositoryInterface&MockObject $jobLog;
    private SchedulerService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ScheduledJobRepositoryInterface::class);
        $this->cronHelper = $this->createMock(CronExpressionHelperInterface::class);
        $this->asyncDispatcher = $this->createMock(AsyncA2ADispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->jobLog = $this->createMock(SchedulerJobLogRepositoryInterface::class);
        $this->jobLog->method('logStart')->willReturn('log-default');

        $this->service = new SchedulerService(
            $this->repository,
            $this->cronHelper,
            $this->asyncDispatcher,
            $this->logger,
            $this->connection,
            $this->jobLog,
        );
    }

    public function testTickReturnsZeroWhenNoDueJobs(): void
    {
        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([]);

        $this->asyncDispatcher->expects($this->never())
            ->method('dispatchAll');

        $this->assertSame(0, $this->service->tick());
    }

    public function testTickExecutesJobAndUpdatesAfterSuccess(): void
    {
        $job = $this->makeJob('job-1', '* * * * *');

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $nextRun = new \DateTimeImmutable('+1 minute');
        $this->cronHelper->expects($this->once())
            ->method('computeNextRun')
            ->willReturn($nextRun);

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-1' => ['status' => 'completed', 'result' => ['status' => 'completed']],
            ]);

        // Phase 1: updateAfterRun with 'running' (before dispatch)
        // Phase 3: updateAfterRun with 'completed' (after dispatch)
        $this->repository->expects($this->exactly(2))
            ->method('updateAfterRun');

        $this->assertSame(1, $this->service->tick());
    }

    public function testTickDisablesOneShotJobAfterSuccess(): void
    {
        $job = $this->makeJob('job-2', null);

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $this->cronHelper->expects($this->never())
            ->method('computeNextRun');

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-2' => ['status' => 'completed', 'result' => ['status' => 'completed']],
            ]);

        // Phase 1: 'running' with null next_run_at; Phase 3: 'completed' with null
        $this->repository->expects($this->exactly(2))
            ->method('updateAfterRun');

        $this->assertSame(1, $this->service->tick());
    }

    public function testTickIncrementsRetryCountOnFailure(): void
    {
        $job = $this->makeJob('job-3', '* * * * *', retryCount: 0, maxRetries: 3);

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-3' => ['status' => 'failed', 'error' => 'Agent returned failed status'],
            ]);

        $this->repository->expects($this->once())
            ->method('updateRetry')
            ->with($job['id'], 1, $this->isString());

        $this->repository->expects($this->never())
            ->method('disableJob');

        $this->assertSame(1, $this->service->tick());
    }

    public function testTickDeadLettersJobWhenMaxRetriesReached(): void
    {
        $job = $this->makeJob('job-4', '* * * * *', retryCount: 2, maxRetries: 3);

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-4' => ['status' => 'failed', 'error' => 'Agent error'],
            ]);

        $this->repository->expects($this->once())
            ->method('disableJob')
            ->with($job['id']);

        $this->repository->expects($this->never())
            ->method('updateRetry');

        $this->assertSame(1, $this->service->tick());
    }

    public function testTickPhase1CommitsBeforeDispatch(): void
    {
        $job = $this->makeJob('job-phase', '* * * * *');

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $this->cronHelper->method('computeNextRun')
            ->willReturn(new \DateTimeImmutable('+1 minute'));

        // Verify transaction is committed before dispatchAll
        $commitCalled = false;
        $this->connection->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use (&$commitCalled): void {
                $commitCalled = true;
            });

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturnCallback(function () use (&$commitCalled): array {
                $this->assertTrue($commitCalled, 'Transaction must be committed before dispatch');

                return ['job-phase' => ['status' => 'completed', 'result' => []]];
            });

        $this->service->tick();
    }

    public function testTickNextRunAtUpdatedBeforeDispatch(): void
    {
        $job = $this->makeJob('job-pre', '0 * * * *');

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $nextRun = new \DateTimeImmutable('+1 hour');
        $this->cronHelper->expects($this->once())
            ->method('computeNextRun')
            ->willReturn($nextRun);

        // Phase 1 updateAfterRun should be called with 'running' status
        $phase1Called = false;
        $this->repository->expects($this->atLeast(1))
            ->method('updateAfterRun')
            ->willReturnCallback(function (string $id, string $status, ?string $nextRunAt) use (&$phase1Called): void {
                if ('running' === $status) {
                    $phase1Called = true;
                    $this->assertNotNull($nextRunAt, 'next_run_at must be set in Phase 1');
                }
            });

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturnCallback(function () use (&$phase1Called): array {
                $this->assertTrue($phase1Called, 'Phase 1 updateAfterRun must happen before dispatch');

                return ['job-pre' => ['status' => 'completed', 'result' => []]];
            });

        $this->service->tick();
    }

    public function testTickMultipleJobsDispatchedTogether(): void
    {
        $job1 = $this->makeJob('job-a', '* * * * *');
        $job2 = $this->makeJob('job-b', '* * * * *');

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job1, $job2]);

        $this->cronHelper->method('computeNextRun')
            ->willReturn(new \DateTimeImmutable('+1 minute'));

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturnCallback(function (array $jobs): array {
                $this->assertCount(2, $jobs, 'Both jobs should be dispatched in a single call');

                return [
                    'job-a' => ['status' => 'completed', 'result' => []],
                    'job-b' => ['status' => 'failed', 'error' => 'timeout'],
                ];
            });

        $this->assertSame(2, $this->service->tick());
    }

    public function testTickLogsStartAndCompletedOnSuccess(): void
    {
        $jobLog = $this->createMock(SchedulerJobLogRepositoryInterface::class);
        $service = new SchedulerService($this->repository, $this->cronHelper, $this->asyncDispatcher, $this->logger, $this->connection, $jobLog);

        $job = $this->makeJob('job-log-ok', '* * * * *');

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $this->cronHelper->method('computeNextRun')
            ->willReturn(new \DateTimeImmutable('+1 minute'));

        $jobLog->expects($this->once())
            ->method('logStart')
            ->with('job-log-ok', 'test-agent', 'test.skill', 'test-job', [])
            ->willReturn('log-42');

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-log-ok' => ['status' => 'completed', 'result' => ['status' => 'completed']],
            ]);

        $jobLog->expects($this->once())
            ->method('logFinish')
            ->with('log-42', 'completed', null, ['status' => 'completed']);

        $service->tick();
    }

    public function testTickLogsFailedOnDispatchError(): void
    {
        $jobLog = $this->createMock(SchedulerJobLogRepositoryInterface::class);
        $service = new SchedulerService($this->repository, $this->cronHelper, $this->asyncDispatcher, $this->logger, $this->connection, $jobLog);

        $job = $this->makeJob('job-log-err', '* * * * *', retryCount: 0, maxRetries: 3);

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $this->cronHelper->method('computeNextRun')
            ->willReturn(new \DateTimeImmutable('+1 minute'));

        $jobLog->expects($this->once())
            ->method('logStart')
            ->willReturn('log-43');

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-log-err' => ['status' => 'failed', 'error' => 'Connection refused'],
            ]);

        $jobLog->expects($this->once())
            ->method('logFinish')
            ->with('log-43', 'failed', 'Connection refused', $this->anything());

        $service->tick();
    }

    public function testRegisterFromManifestSkipsEmptyScheduledJobs(): void
    {
        $this->repository->expects($this->never())
            ->method('registerJob');

        $count = $this->service->registerFromManifest('test-agent', []);
        $this->assertSame(0, $count);
    }

    public function testRegisterFromManifestRegistersJobs(): void
    {
        $manifest = [
            'scheduled_jobs' => [
                [
                    'name' => 'daily-sync',
                    'skill_id' => 'sync.run',
                    'cron_expression' => '0 0 * * *',
                    'payload' => ['mode' => 'full'],
                    'max_retries' => 3,
                    'retry_delay_seconds' => 60,
                    'timezone' => 'UTC',
                ],
            ],
        ];

        $nextRun = new \DateTimeImmutable('+1 day');
        $this->cronHelper->expects($this->once())
            ->method('computeNextRun')
            ->with('0 0 * * *', 'UTC')
            ->willReturn($nextRun);

        $this->repository->expects($this->once())
            ->method('registerJob')
            ->with('test-agent', 'daily-sync', 'sync.run', ['mode' => 'full'], '0 0 * * *', 3, 60, 'UTC', $this->isString());

        $count = $this->service->registerFromManifest('test-agent', $manifest);
        $this->assertSame(1, $count);
    }

    public function testCatchUpPolicyRunsOverdueJobOnce(): void
    {
        $job = $this->makeJob('job-catchup', '0 * * * *');

        $this->repository->expects($this->once())
            ->method('findDueJobs')
            ->willReturn([$job]);

        $nextRun = new \DateTimeImmutable('+1 hour');
        $this->cronHelper->expects($this->once())
            ->method('computeNextRun')
            ->willReturn($nextRun);

        $this->asyncDispatcher->expects($this->once())
            ->method('dispatchAll')
            ->willReturn([
                'job-catchup' => ['status' => 'completed', 'result' => ['status' => 'completed']],
            ]);

        $this->repository->expects($this->atLeast(1))
            ->method('updateAfterRun');

        $this->assertSame(1, $this->service->tick());
    }

    /**
     * @return array<string, mixed>
     */
    private function makeJob(
        string $id,
        ?string $cronExpression,
        int $retryCount = 0,
        int $maxRetries = 3,
    ): array {
        return [
            'id' => $id,
            'agent_name' => 'test-agent',
            'job_name' => 'test-job',
            'skill_id' => 'test.skill',
            'payload' => '{}',
            'cron_expression' => $cronExpression,
            'timezone' => 'UTC',
            'retry_count' => $retryCount,
            'max_retries' => $maxRetries,
            'retry_delay_seconds' => 60,
        ];
    }
}
