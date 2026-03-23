<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventBus;

use App\AgentRegistry\AgentRegistryInterface;
use App\EventBus\EventBus;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

/**
 * Tests the internal event bus logic for broadcasting events to agents.
 *
 * @see docs/specs/a2a-protocol.md (Section: Core Events Broadcasting)
 */
final class EventBusTest extends Unit
{
    private AgentRegistryInterface&MockObject $registry;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(AgentRegistryInterface::class);
        $this->eventBus = new EventBus($this->registry, new NullLogger());
    }

    public function testDispatchDoesNotCallRegistryWhenNoEnabledAgents(): void
    {
        $this->registry->expects($this->once())
            ->method('findEnabled')
            ->willReturn([]);

        $this->eventBus->dispatch('message.created', ['text' => 'hello']);
    }

    public function testDispatchSkipsAgentNotSubscribedToEvent(): void
    {
        $agent = [
            'name' => 'test-agent',
            'manifest' => json_encode(['events' => ['message.deleted'], 'commands' => []]),
        ];

        $this->registry->expects($this->once())
            ->method('findEnabled')
            ->willReturn([$agent]);

        // Should complete without error — agent is not subscribed to 'message.created'
        $this->eventBus->dispatch('message.created', ['text' => 'hello']);
    }

    public function testDispatchProcessesAgentSubscribedToEvent(): void
    {
        $agent = [
            'name' => 'test-agent',
            'manifest' => json_encode(['events' => ['message.created'], 'commands' => []]),
        ];

        $this->registry->expects($this->once())
            ->method('findEnabled')
            ->willReturn([$agent]);

        // Should complete without error — agent IS subscribed to 'message.created'
        $this->eventBus->dispatch('message.created', ['text' => 'hello']);
    }

    public function testDispatchHandlesManifestAsArray(): void
    {
        $agent = [
            'name' => 'test-agent',
            'manifest' => ['events' => ['message.created'], 'commands' => []],
        ];

        $this->registry->expects($this->once())
            ->method('findEnabled')
            ->willReturn([$agent]);

        $this->eventBus->dispatch('message.created', []);
    }
}
