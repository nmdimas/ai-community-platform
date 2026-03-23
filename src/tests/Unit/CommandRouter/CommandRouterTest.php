<?php

declare(strict_types=1);

namespace App\Tests\Unit\CommandRouter;

use App\AgentRegistry\AgentRegistryInterface;
use App\CommandRouter\CommandRouter;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests how incoming slash-commands are directed to registered agents.
 *
 * @see docs/specs/a2a-protocol.md (Section: Commands Routing & Handling)
 */
final class CommandRouterTest extends Unit
{
    private AgentRegistryInterface&MockObject $registry;
    private CommandRouter $router;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(AgentRegistryInterface::class);
        $this->router = new CommandRouter($this->registry);
    }

    public function testResolveReturnsNullWhenNoEnabledAgents(): void
    {
        $this->registry->method('findEnabled')->willReturn([]);

        $this->assertNull($this->router->resolve('/wiki'));
    }

    public function testResolveReturnsAgentThatHandlesCommand(): void
    {
        $agent = [
            'name' => 'knowledge-base',
            'manifest' => json_encode(['commands' => ['/wiki', '/search'], 'events' => []]),
        ];

        $this->registry->method('findEnabled')->willReturn([$agent]);

        $result = $this->router->resolve('/wiki');
        $this->assertNotNull($result);
        $this->assertSame('knowledge-base', $result['name']);
    }

    public function testResolveReturnsNullWhenCommandNotHandled(): void
    {
        $agent = [
            'name' => 'knowledge-base',
            'manifest' => json_encode(['commands' => ['/wiki'], 'events' => []]),
        ];

        $this->registry->method('findEnabled')->willReturn([$agent]);

        $this->assertNull($this->router->resolve('/unknown'));
    }

    public function testResolveHandlesManifestAsArray(): void
    {
        $agent = [
            'name' => 'test-agent',
            'manifest' => ['commands' => ['/test'], 'events' => []],
        ];

        $this->registry->method('findEnabled')->willReturn([$agent]);

        $result = $this->router->resolve('/test');
        $this->assertNotNull($result);
    }

    public function testUnavailableResponseContainsCommand(): void
    {
        $response = $this->router->unavailableResponse('/unknown');
        $this->assertStringContainsString('/unknown', $response);
        $this->assertStringContainsString('недоступна', $response);
    }
}
