<?php

declare(strict_types=1);

namespace App\Tests\Unit\AgentProject\DTO;

use App\AgentProject\DTO\AgentProject;
use App\AgentProject\GitAuthMode;
use App\AgentProject\GitProvider;
use App\AgentProject\ProjectStatus;
use App\AgentProject\SandboxType;
use Codeception\Test\Unit;

final class AgentProjectTest extends Unit
{
    public function testFromDatabaseRowWithFullData(): void
    {
        $row = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'slug' => 'hello-agent',
            'name' => 'Hello Agent',
            'agent_name' => 'hello-agent',
            'git_provider' => 'github',
            'git_host_url' => 'https://github.com',
            'git_remote_url' => 'https://github.com/nmdimas/a2a-hello-agent.git',
            'git_default_branch' => 'main',
            'git_auth_mode' => 'token',
            'credential_ref' => 'env:HELLO_AGENT_GIT_TOKEN',
            'checkout_path' => 'projects/hello-agent/',
            'sandbox_type' => 'template',
            'sandbox_template_id' => 'php-symfony-agent',
            'sandbox_image_ref' => null,
            'sandbox_compose_ref' => null,
            'status' => 'active',
            'created_at' => '2026-03-12T10:00:00+00:00',
            'updated_at' => '2026-03-12T12:00:00+00:00',
        ];

        $project = AgentProject::fromDatabaseRow($row);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $project->id);
        $this->assertSame('hello-agent', $project->slug);
        $this->assertSame('Hello Agent', $project->name);
        $this->assertSame('hello-agent', $project->agentName);
        $this->assertSame(GitProvider::GitHub, $project->gitProvider);
        $this->assertSame('https://github.com', $project->gitHostUrl);
        $this->assertSame('https://github.com/nmdimas/a2a-hello-agent.git', $project->gitRemoteUrl);
        $this->assertSame('main', $project->gitDefaultBranch);
        $this->assertSame(GitAuthMode::Token, $project->gitAuthMode);
        $this->assertSame('env:HELLO_AGENT_GIT_TOKEN', $project->credentialRef);
        $this->assertSame('projects/hello-agent/', $project->checkoutPath);
        $this->assertSame(SandboxType::Template, $project->sandboxType);
        $this->assertSame('php-symfony-agent', $project->sandboxTemplateId);
        $this->assertNull($project->sandboxImageRef);
        $this->assertNull($project->sandboxComposeRef);
        $this->assertSame(ProjectStatus::Active, $project->status);
        $this->assertInstanceOf(\DateTimeImmutable::class, $project->createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $project->updatedAt);
    }

    public function testFromDatabaseRowWithMinimalData(): void
    {
        $project = AgentProject::fromDatabaseRow([]);

        $this->assertSame('', $project->id);
        $this->assertSame('', $project->slug);
        $this->assertSame('', $project->name);
        $this->assertNull($project->agentName);
        $this->assertSame(GitProvider::GitHub, $project->gitProvider);
        $this->assertSame('', $project->gitHostUrl);
        $this->assertSame('', $project->gitRemoteUrl);
        $this->assertSame('main', $project->gitDefaultBranch);
        $this->assertSame(GitAuthMode::None, $project->gitAuthMode);
        $this->assertNull($project->credentialRef);
        $this->assertSame('', $project->checkoutPath);
        $this->assertSame(SandboxType::Template, $project->sandboxType);
        $this->assertNull($project->sandboxTemplateId);
        $this->assertNull($project->sandboxImageRef);
        $this->assertNull($project->sandboxComposeRef);
        $this->assertSame(ProjectStatus::Draft, $project->status);
        $this->assertNull($project->createdAt);
        $this->assertNull($project->updatedAt);
    }

    public function testFromDatabaseRowNullAgentNameWhenEmpty(): void
    {
        $project = AgentProject::fromDatabaseRow(['agent_name' => '']);

        $this->assertNull($project->agentName);
    }

    public function testFromDatabaseRowNullCredentialRefWhenEmpty(): void
    {
        $project = AgentProject::fromDatabaseRow(['credential_ref' => '']);

        $this->assertNull($project->credentialRef);
    }

    public function testFromDatabaseRowWithSelfHostedProvider(): void
    {
        $project = AgentProject::fromDatabaseRow([
            'git_provider' => 'self_hosted',
            'git_host_url' => 'https://gitlab.example.com',
        ]);

        $this->assertSame(GitProvider::SelfHosted, $project->gitProvider);
        $this->assertSame('https://gitlab.example.com', $project->gitHostUrl);
    }

    public function testFromDatabaseRowWithCustomImageSandbox(): void
    {
        $project = AgentProject::fromDatabaseRow([
            'sandbox_type' => 'custom_image',
            'sandbox_image_ref' => 'ghcr.io/org/agent:latest',
        ]);

        $this->assertSame(SandboxType::CustomImage, $project->sandboxType);
        $this->assertSame('ghcr.io/org/agent:latest', $project->sandboxImageRef);
    }

    public function testFromDatabaseRowWithComposeServiceSandbox(): void
    {
        $project = AgentProject::fromDatabaseRow([
            'sandbox_type' => 'compose_service',
            'sandbox_compose_ref' => 'compose.agent-hello.yaml:hello-agent',
        ]);

        $this->assertSame(SandboxType::ComposeService, $project->sandboxType);
        $this->assertSame('compose.agent-hello.yaml:hello-agent', $project->sandboxComposeRef);
    }

    public function testFromDatabaseRowWithUnknownEnumValuesFallsToDefault(): void
    {
        $project = AgentProject::fromDatabaseRow([
            'git_provider' => 'unknown_provider',
            'git_auth_mode' => 'unknown_mode',
            'sandbox_type' => 'unknown_type',
            'status' => 'unknown_status',
        ]);

        $this->assertSame(GitProvider::GitHub, $project->gitProvider);
        $this->assertSame(GitAuthMode::None, $project->gitAuthMode);
        $this->assertSame(SandboxType::Template, $project->sandboxType);
        $this->assertSame(ProjectStatus::Draft, $project->status);
    }

    public function testToArrayRoundtrip(): void
    {
        $row = [
            'id' => 'roundtrip-id',
            'slug' => 'news-maker',
            'name' => 'News Maker Agent',
            'agent_name' => 'news-maker-agent',
            'git_provider' => 'github',
            'git_host_url' => 'https://github.com',
            'git_remote_url' => 'https://github.com/nmdimas/a2a-news-maker-agent.git',
            'git_default_branch' => 'main',
            'git_auth_mode' => 'ssh_key',
            'credential_ref' => 'env:NEWS_MAKER_SSH_KEY',
            'checkout_path' => 'projects/news-maker/',
            'sandbox_type' => 'custom_image',
            'sandbox_template_id' => null,
            'sandbox_image_ref' => 'ghcr.io/org/news-maker:latest',
            'sandbox_compose_ref' => null,
            'status' => 'draft',
            'created_at' => '2026-03-12T10:00:00+00:00',
            'updated_at' => '2026-03-12T10:00:00+00:00',
        ];

        $project = AgentProject::fromDatabaseRow($row);
        $output = $project->toArray();

        $this->assertSame('roundtrip-id', $output['id']);
        $this->assertSame('news-maker', $output['slug']);
        $this->assertSame('News Maker Agent', $output['name']);
        $this->assertSame('news-maker-agent', $output['agent_name']);
        $this->assertSame('github', $output['git_provider']);
        $this->assertSame('https://github.com', $output['git_host_url']);
        $this->assertSame('https://github.com/nmdimas/a2a-news-maker-agent.git', $output['git_remote_url']);
        $this->assertSame('main', $output['git_default_branch']);
        $this->assertSame('ssh_key', $output['git_auth_mode']);
        $this->assertSame('env:NEWS_MAKER_SSH_KEY', $output['credential_ref']);
        $this->assertSame('projects/news-maker/', $output['checkout_path']);
        $this->assertSame('custom_image', $output['sandbox_type']);
        $this->assertNull($output['sandbox_template_id']);
        $this->assertSame('ghcr.io/org/news-maker:latest', $output['sandbox_image_ref']);
        $this->assertNull($output['sandbox_compose_ref']);
        $this->assertSame('draft', $output['status']);
        $this->assertSame('2026-03-12T10:00:00+00:00', $output['created_at']);
    }

    public function testToArrayWithNullTimestamps(): void
    {
        $project = AgentProject::fromDatabaseRow([]);
        $output = $project->toArray();

        $this->assertNull($output['created_at']);
        $this->assertNull($output['updated_at']);
    }
}
