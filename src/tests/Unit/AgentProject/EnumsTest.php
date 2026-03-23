<?php

declare(strict_types=1);

namespace App\Tests\Unit\AgentProject;

use App\AgentProject\GitAuthMode;
use App\AgentProject\GitProvider;
use App\AgentProject\ProjectStatus;
use App\AgentProject\SandboxType;
use Codeception\Test\Unit;

final class EnumsTest extends Unit
{
    public function testGitProviderCases(): void
    {
        $this->assertSame('github', GitProvider::GitHub->value);
        $this->assertSame('gitlab', GitProvider::GitLab->value);
        $this->assertSame('self_hosted', GitProvider::SelfHosted->value);
    }

    public function testGitProviderTryFrom(): void
    {
        $this->assertSame(GitProvider::GitHub, GitProvider::tryFrom('github'));
        $this->assertSame(GitProvider::GitLab, GitProvider::tryFrom('gitlab'));
        $this->assertSame(GitProvider::SelfHosted, GitProvider::tryFrom('self_hosted'));
        $this->assertNull(GitProvider::tryFrom('unknown'));
        $this->assertNull(GitProvider::tryFrom(''));
    }

    public function testSandboxTypeCases(): void
    {
        $this->assertSame('template', SandboxType::Template->value);
        $this->assertSame('custom_image', SandboxType::CustomImage->value);
        $this->assertSame('compose_service', SandboxType::ComposeService->value);
    }

    public function testSandboxTypeTryFrom(): void
    {
        $this->assertSame(SandboxType::Template, SandboxType::tryFrom('template'));
        $this->assertSame(SandboxType::CustomImage, SandboxType::tryFrom('custom_image'));
        $this->assertSame(SandboxType::ComposeService, SandboxType::tryFrom('compose_service'));
        $this->assertNull(SandboxType::tryFrom('unknown'));
        $this->assertNull(SandboxType::tryFrom(''));
    }

    public function testGitAuthModeCases(): void
    {
        $this->assertSame('token', GitAuthMode::Token->value);
        $this->assertSame('ssh_key', GitAuthMode::SshKey->value);
        $this->assertSame('none', GitAuthMode::None->value);
    }

    public function testGitAuthModeTryFrom(): void
    {
        $this->assertSame(GitAuthMode::Token, GitAuthMode::tryFrom('token'));
        $this->assertSame(GitAuthMode::SshKey, GitAuthMode::tryFrom('ssh_key'));
        $this->assertSame(GitAuthMode::None, GitAuthMode::tryFrom('none'));
        $this->assertNull(GitAuthMode::tryFrom('unknown'));
        $this->assertNull(GitAuthMode::tryFrom(''));
    }

    public function testProjectStatusCases(): void
    {
        $this->assertSame('draft', ProjectStatus::Draft->value);
        $this->assertSame('active', ProjectStatus::Active->value);
        $this->assertSame('archived', ProjectStatus::Archived->value);
    }

    public function testProjectStatusTryFrom(): void
    {
        $this->assertSame(ProjectStatus::Draft, ProjectStatus::tryFrom('draft'));
        $this->assertSame(ProjectStatus::Active, ProjectStatus::tryFrom('active'));
        $this->assertSame(ProjectStatus::Archived, ProjectStatus::tryFrom('archived'));
        $this->assertNull(ProjectStatus::tryFrom('unknown'));
        $this->assertNull(ProjectStatus::tryFrom(''));
    }
}
