<?php

declare(strict_types=1);

namespace App\Tests\Functional\Scheduler;

use App\Scheduler\ScheduledJobRepository;

final class AgentInstallSchedulerCest
{
    private const INTERNAL_TOKEN = 'test-internal-token';

    private function login(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->sendPost('/admin/login', [
            '_username' => 'admin',
            '_password' => 'test-password',
        ]);
        $I->seeResponseCodeIs(200);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifestWithScheduledJobs(string $name): array
    {
        return [
            'name' => $name,
            'version' => '1.0.0',
            'description' => 'Scheduler test agent',
            'permissions' => ['admin'],
            'commands' => ['/test'],
            'events' => ['message.created'],
            'a2a_endpoint' => sprintf('http://%s/a2a', $name),
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
    }

    public function installingAgentWithScheduledJobsCreatesRows(\FunctionalTester $I): void
    {
        $name = 'scheduler-install-agent-'.bin2hex(random_bytes(4));

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Platform-Internal-Token', self::INTERNAL_TOKEN);
        $I->sendPost('/api/v1/internal/agents/register', json_encode($this->manifestWithScheduledJobs($name), JSON_THROW_ON_ERROR));
        $I->seeResponseCodeIs(200);

        $this->login($I);

        $I->sendPost(sprintf('/api/v1/internal/agents/%s/install', $name));
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => 'installed', 'name' => $name]);

        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);
        $all = $repo->findAll();
        $jobs = array_values(array_filter($all, static fn (array $j): bool => $j['agent_name'] === $name));

        $I->assertCount(1, $jobs);
        $I->assertSame('daily-sync', $jobs[0]['job_name']);
        $I->assertSame('sync.run', $jobs[0]['skill_id']);
        $I->assertTrue((bool) $jobs[0]['enabled']);
    }

    public function uninstallingAgentRemovesScheduledJobs(\FunctionalTester $I): void
    {
        $name = 'scheduler-uninstall-agent-'.bin2hex(random_bytes(4));

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Platform-Internal-Token', self::INTERNAL_TOKEN);
        $I->sendPost('/api/v1/internal/agents/register', json_encode($this->manifestWithScheduledJobs($name), JSON_THROW_ON_ERROR));
        $I->seeResponseCodeIs(200);

        $this->login($I);

        $I->sendPost(sprintf('/api/v1/internal/agents/%s/install', $name));
        $I->seeResponseCodeIs(200);

        // Verify jobs were created
        /** @var ScheduledJobRepository $repo */
        $repo = $I->grabService(ScheduledJobRepository::class);
        $all = $repo->findAll();
        $jobs = array_filter($all, static fn (array $j): bool => $j['agent_name'] === $name);
        $I->assertCount(1, $jobs);

        // Disable then delete
        $I->sendPost(sprintf('/api/v1/internal/agents/%s/disable', $name));
        $I->seeResponseCodeIs(200);

        $I->sendDelete(sprintf('/api/v1/internal/agents/%s', $name));
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'uninstalled', 'name' => $name]);

        // Verify jobs were removed
        $all = $repo->findAll();
        $remaining = array_filter($all, static fn (array $j): bool => $j['agent_name'] === $name);
        $I->assertCount(0, $remaining);
    }
}
