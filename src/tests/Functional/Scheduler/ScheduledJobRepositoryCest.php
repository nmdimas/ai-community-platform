<?php

declare(strict_types=1);

namespace App\Tests\Functional\Scheduler;

use App\Scheduler\ScheduledJobRepository;

final class ScheduledJobRepositoryCest
{
    private function makeNextRunAt(): string
    {
        return (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:sP');
    }

    public function registerAndFindJob(\FunctionalTester $I): void
    {
        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);

        $agentName = 'test-agent-'.bin2hex(random_bytes(4));
        $jobName = 'test-job-'.bin2hex(random_bytes(4));

        $repo->registerJob(
            $agentName,
            $jobName,
            'test.skill',
            ['key' => 'value'],
            '0 * * * *',
            3,
            60,
            'UTC',
            $this->makeNextRunAt(),
        );

        $all = $repo->findAll();
        $found = array_filter($all, static fn (array $j): bool => $j['agent_name'] === $agentName && $j['job_name'] === $jobName);

        $I->assertCount(1, $found);
        $job = array_values($found)[0];
        $I->assertSame('test.skill', $job['skill_id']);
        $I->assertTrue((bool) $job['enabled']);
    }

    public function registerJobIsIdempotent(\FunctionalTester $I): void
    {
        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);

        $agentName = 'test-agent-'.bin2hex(random_bytes(4));
        $jobName = 'idempotent-job';

        $repo->registerJob($agentName, $jobName, 'skill.v1', [], '0 * * * *', 3, 60, 'UTC', $this->makeNextRunAt());
        $repo->registerJob($agentName, $jobName, 'skill.v2', [], '0 * * * *', 3, 60, 'UTC', $this->makeNextRunAt());

        $all = $repo->findAll();
        $found = array_filter($all, static fn (array $j): bool => $j['agent_name'] === $agentName && $j['job_name'] === $jobName);

        $I->assertCount(1, $found);
        $job = array_values($found)[0];
        $I->assertSame('skill.v2', $job['skill_id']);
    }

    public function findDueJobsReturnsDueJobs(\FunctionalTester $I): void
    {
        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);

        $agentName = 'test-agent-'.bin2hex(random_bytes(4));
        $jobName = 'due-job';

        // Register a job with next_run_at in the past
        $pastRunAt = (new \DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:sP');
        $repo->registerJob($agentName, $jobName, 'test.skill', [], '* * * * *', 3, 60, 'UTC', $pastRunAt);

        $due = $repo->findDueJobs();
        $found = array_filter($due, static fn (array $j): bool => $j['agent_name'] === $agentName && $j['job_name'] === $jobName);

        $I->assertCount(1, $found);
    }

    public function updateAfterRunSetsStatusAndNextRun(\FunctionalTester $I): void
    {
        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);

        $agentName = 'test-agent-'.bin2hex(random_bytes(4));
        $jobName = 'update-job';

        $repo->registerJob($agentName, $jobName, 'test.skill', [], '* * * * *', 3, 60, 'UTC', $this->makeNextRunAt());

        $all = $repo->findAll();
        $found = array_values(array_filter($all, static fn (array $j): bool => $j['agent_name'] === $agentName));
        $I->assertCount(1, $found);
        $id = (string) $found[0]['id'];

        $nextRun = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:sP');
        $repo->updateAfterRun($id, 'completed', $nextRun);

        $updated = $repo->findById($id);
        $I->assertNotNull($updated);
        $I->assertSame('completed', $updated['last_status']);
        $I->assertNotNull($updated['last_run_at']);
    }

    public function deleteByAgentRemovesAllJobs(\FunctionalTester $I): void
    {
        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);

        $agentName = 'delete-agent-'.bin2hex(random_bytes(4));

        $repo->registerJob($agentName, 'job-1', 'skill.a', [], null, 3, 60, 'UTC', $this->makeNextRunAt());
        $repo->registerJob($agentName, 'job-2', 'skill.b', [], null, 3, 60, 'UTC', $this->makeNextRunAt());

        $deleted = $repo->deleteByAgent($agentName);
        $I->assertSame(2, $deleted);

        $all = $repo->findAll();
        $remaining = array_filter($all, static fn (array $j): bool => $j['agent_name'] === $agentName);
        $I->assertCount(0, $remaining);
    }
}
