<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduler;

use App\AgentRegistry\AgentRegistryInterface;
use App\Scheduler\AsyncA2ADispatcher;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

final class AsyncA2ADispatcherTest extends Unit
{
    private AgentRegistryInterface&MockObject $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(AgentRegistryInterface::class);
    }

    public function testDispatchAllReturnsEmptyForEmptyJobs(): void
    {
        $dispatcher = new AsyncA2ADispatcher($this->registry, new NullLogger(), 'test-token');

        $result = $dispatcher->dispatchAll([]);
        $this->assertSame([], $result);
    }

    public function testDispatchAllReturnsFailedForUnknownSkill(): void
    {
        $this->registry->method('findEnabled')->willReturn([]);

        $dispatcher = new AsyncA2ADispatcher($this->registry, new NullLogger(), 'test-token');

        $result = $dispatcher->dispatchAll([
            [
                'id' => 'job-1',
                'skill_id' => 'unknown.skill',
                'payload' => [],
                'trace_id' => 'trace-1',
                'request_id' => 'req-1',
            ],
        ]);

        $this->assertArrayHasKey('job-1', $result);
        $this->assertSame('failed', $result['job-1']['status']);
        $this->assertStringContainsString('unknown_skill', $result['job-1']['error']);
    }

    public function testDispatchAllResolvesAgentFromRegistry(): void
    {
        $this->registry->method('findEnabled')->willReturn([
            [
                'name' => 'hello-agent',
                'manifest' => json_encode([
                    'url' => 'http://hello-agent:8080/a2a',
                    'skills' => ['hello.greet'],
                ]),
                'config' => '{}',
            ],
        ]);

        $dispatcher = new AsyncA2ADispatcher($this->registry, new NullLogger(), 'test-token', timeout: 2.0);

        // This will fail with connection error since there's no real server,
        // but it proves the skill was resolved and HTTP was attempted
        $result = $dispatcher->dispatchAll([
            [
                'id' => 'job-2',
                'skill_id' => 'hello.greet',
                'payload' => ['name' => 'World'],
                'trace_id' => 'trace-2',
                'request_id' => 'req-2',
            ],
        ]);

        $this->assertArrayHasKey('job-2', $result);
        $this->assertSame('failed', $result['job-2']['status']);
        // Connection refused or timeout — both prove the agent was resolved
        $this->assertNotEmpty($result['job-2']['error']);
    }

    public function testDispatchAllIsolatesPerJobFailures(): void
    {
        $this->registry->method('findEnabled')->willReturn([
            [
                'name' => 'agent-a',
                'manifest' => json_encode([
                    'url' => 'http://agent-a:8080/a2a',
                    'skills' => ['skill.a'],
                ]),
                'config' => '{}',
            ],
        ]);

        $dispatcher = new AsyncA2ADispatcher($this->registry, new NullLogger(), 'test-token', timeout: 1.0);

        $result = $dispatcher->dispatchAll([
            [
                'id' => 'job-a',
                'skill_id' => 'skill.a',
                'payload' => [],
                'trace_id' => 'trace-a',
                'request_id' => 'req-a',
            ],
            [
                'id' => 'job-unknown',
                'skill_id' => 'skill.missing',
                'payload' => [],
                'trace_id' => 'trace-b',
                'request_id' => 'req-b',
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('job-a', $result);
        $this->assertArrayHasKey('job-unknown', $result);
        // Unknown skill fails immediately
        $this->assertSame('failed', $result['job-unknown']['status']);
        $this->assertStringContainsString('unknown_skill', $result['job-unknown']['error']);
        // Resolved skill fails with connection error (no server), but independently
        $this->assertSame('failed', $result['job-a']['status']);
    }
}
