<?php

declare(strict_types=1);

namespace App\Tests\Unit\AgentProject;

use App\AgentProject\EnvCredentialResolver;
use Codeception\Test\Unit;

final class EnvCredentialResolverTest extends Unit
{
    private EnvCredentialResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new EnvCredentialResolver();
    }

    public function testResolvesEnvVarReference(): void
    {
        putenv('TEST_AGENT_TOKEN=secret-value-123');

        $result = $this->resolver->resolve('env:TEST_AGENT_TOKEN');

        $this->assertSame('secret-value-123', $result);

        putenv('TEST_AGENT_TOKEN');
    }

    public function testReturnsNullForUnsetEnvVar(): void
    {
        putenv('UNSET_AGENT_TOKEN');

        $result = $this->resolver->resolve('env:UNSET_AGENT_TOKEN');

        $this->assertNull($result);
    }

    public function testReturnsNullForUnsupportedScheme(): void
    {
        $result = $this->resolver->resolve('vault:agent/hello/git-token');

        $this->assertNull($result);
    }

    public function testReturnsNullForEmptyVarName(): void
    {
        $result = $this->resolver->resolve('env:');

        $this->assertNull($result);
    }

    public function testReturnsNullForPlainString(): void
    {
        $result = $this->resolver->resolve('MY_TOKEN');

        $this->assertNull($result);
    }

    public function testReturnsNullForEmptyString(): void
    {
        $result = $this->resolver->resolve('');

        $this->assertNull($result);
    }

    public function testReturnsNullWhenEnvVarIsEmpty(): void
    {
        putenv('EMPTY_AGENT_TOKEN=');

        $result = $this->resolver->resolve('env:EMPTY_AGENT_TOKEN');

        $this->assertNull($result);

        putenv('EMPTY_AGENT_TOKEN');
    }
}
