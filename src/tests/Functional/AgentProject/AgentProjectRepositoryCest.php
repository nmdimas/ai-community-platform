<?php

declare(strict_types=1);

namespace App\Tests\Functional\AgentProject;

use App\AgentProject\AgentProjectRepositoryInterface;
use App\AgentProject\DTO\AgentProject;
use App\AgentProject\GitAuthMode;
use App\AgentProject\GitProvider;
use App\AgentProject\ProjectStatus;
use App\AgentProject\SandboxType;

final class AgentProjectRepositoryCest
{
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function makeProject(string $slug, ?string $agentName = null): AgentProject
    {
        return new AgentProject(
            id: $this->generateUuid(),
            slug: $slug,
            name: ucwords(str_replace('-', ' ', $slug)),
            agentName: $agentName,
            gitProvider: GitProvider::GitHub,
            gitHostUrl: 'https://github.com',
            gitRemoteUrl: sprintf('https://github.com/nmdimas/%s.git', $slug),
            gitDefaultBranch: 'main',
            gitAuthMode: GitAuthMode::Token,
            credentialRef: sprintf('env:%s_GIT_TOKEN', strtoupper(str_replace('-', '_', $slug))),
            checkoutPath: sprintf('projects/%s/', $slug),
            sandboxType: SandboxType::Template,
            sandboxTemplateId: 'php-symfony-agent',
            sandboxImageRef: null,
            sandboxComposeRef: null,
            status: ProjectStatus::Draft,
        );
    }

    public function createAndFindBySlug(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $slug = 'test-agent-'.bin2hex(random_bytes(4));
        $project = $this->makeProject($slug);

        $repo->create($project);

        $found = $repo->findBySlug($slug);

        $I->assertNotNull($found);
        $I->assertSame($slug, $found->slug);
        $I->assertSame('github', $found->gitProvider->value);
        $I->assertSame('token', $found->gitAuthMode->value);
        $I->assertSame('template', $found->sandboxType->value);
        $I->assertSame('php-symfony-agent', $found->sandboxTemplateId);
        $I->assertSame('draft', $found->status->value);
        $I->assertNotNull($found->createdAt);
        $I->assertNotNull($found->updatedAt);
    }

    public function findBySlugReturnsNullForMissing(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $result = $repo->findBySlug('nonexistent-slug-'.bin2hex(random_bytes(4)));

        $I->assertNull($result);
    }

    public function createAndFindByAgentName(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $slug = 'linked-agent-'.bin2hex(random_bytes(4));
        $agentName = 'linked-agent-'.bin2hex(random_bytes(4));
        $project = $this->makeProject($slug, $agentName);

        $repo->create($project);

        $found = $repo->findByAgentName($agentName);

        $I->assertNotNull($found);
        $I->assertSame($slug, $found->slug);
        $I->assertSame($agentName, $found->agentName);
    }

    public function findByAgentNameReturnsNullForMissing(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $result = $repo->findByAgentName('nonexistent-agent-'.bin2hex(random_bytes(4)));

        $I->assertNull($result);
    }

    public function updateProject(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $slug = 'update-agent-'.bin2hex(random_bytes(4));
        $project = $this->makeProject($slug);
        $repo->create($project);

        $updated = new AgentProject(
            id: $project->id,
            slug: $project->slug,
            name: 'Updated Name',
            agentName: $project->agentName,
            gitProvider: GitProvider::GitLab,
            gitHostUrl: 'https://gitlab.com',
            gitRemoteUrl: $project->gitRemoteUrl,
            gitDefaultBranch: 'develop',
            gitAuthMode: GitAuthMode::SshKey,
            credentialRef: 'env:UPDATED_SSH_KEY',
            checkoutPath: $project->checkoutPath,
            sandboxType: SandboxType::CustomImage,
            sandboxTemplateId: null,
            sandboxImageRef: 'ghcr.io/org/agent:latest',
            sandboxComposeRef: null,
            status: ProjectStatus::Active,
        );

        $repo->update($updated);

        $found = $repo->findBySlug($slug);

        $I->assertNotNull($found);
        $I->assertSame('Updated Name', $found->name);
        $I->assertSame('gitlab', $found->gitProvider->value);
        $I->assertSame('develop', $found->gitDefaultBranch);
        $I->assertSame('ssh_key', $found->gitAuthMode->value);
        $I->assertSame('env:UPDATED_SSH_KEY', $found->credentialRef);
        $I->assertSame('custom_image', $found->sandboxType->value);
        $I->assertSame('ghcr.io/org/agent:latest', $found->sandboxImageRef);
        $I->assertNull($found->sandboxTemplateId);
        $I->assertSame('active', $found->status->value);
    }

    public function deleteProject(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $slug = 'delete-agent-'.bin2hex(random_bytes(4));
        $project = $this->makeProject($slug);
        $repo->create($project);

        $deleted = $repo->delete($slug);

        $I->assertTrue($deleted);
        $I->assertNull($repo->findBySlug($slug));
    }

    public function deleteReturnsFalseForMissing(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $result = $repo->delete('nonexistent-slug-'.bin2hex(random_bytes(4)));

        $I->assertFalse($result);
    }

    public function findAllReturnsAllProjects(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $prefix = 'findall-'.bin2hex(random_bytes(4));
        $slugA = $prefix.'-aaa';
        $slugB = $prefix.'-bbb';

        $repo->create($this->makeProject($slugA));
        $repo->create($this->makeProject($slugB));

        $all = $repo->findAll();
        $found = array_filter($all, static fn (AgentProject $p): bool => str_starts_with($p->slug, $prefix));

        $I->assertCount(2, $found);
    }

    public function projectWithNoCredentialRefStoresNull(\FunctionalTester $I): void
    {
        /** @var AgentProjectRepositoryInterface $repo */
        $repo = $I->grabService(AgentProjectRepositoryInterface::class);

        $slug = 'public-agent-'.bin2hex(random_bytes(4));
        $project = new AgentProject(
            id: $this->generateUuid(),
            slug: $slug,
            name: 'Public Agent',
            agentName: null,
            gitProvider: GitProvider::GitHub,
            gitHostUrl: 'https://github.com',
            gitRemoteUrl: 'https://github.com/org/public-agent.git',
            gitDefaultBranch: 'main',
            gitAuthMode: GitAuthMode::None,
            credentialRef: null,
            checkoutPath: sprintf('projects/%s/', $slug),
            sandboxType: SandboxType::ComposeService,
            sandboxTemplateId: null,
            sandboxImageRef: null,
            sandboxComposeRef: 'compose.agent-public.yaml:public-agent',
            status: ProjectStatus::Draft,
        );

        $repo->create($project);

        $found = $repo->findBySlug($slug);

        $I->assertNotNull($found);
        $I->assertNull($found->credentialRef);
        $I->assertNull($found->agentName);
        $I->assertSame('none', $found->gitAuthMode->value);
        $I->assertSame('compose.agent-public.yaml:public-agent', $found->sandboxComposeRef);
    }
}
