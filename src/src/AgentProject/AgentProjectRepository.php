<?php

declare(strict_types=1);

namespace App\AgentProject;

use App\AgentProject\DTO\AgentProject;
use Doctrine\DBAL\Connection;

final class AgentProjectRepository implements AgentProjectRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function create(AgentProject $project): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
            INSERT INTO agent_projects (
                id, slug, name, agent_name,
                git_provider, git_host_url, git_remote_url, git_default_branch,
                git_auth_mode, credential_ref, checkout_path,
                sandbox_type, sandbox_template_id, sandbox_image_ref, sandbox_compose_ref,
                status, created_at, updated_at
            ) VALUES (
                :id, :slug, :name, :agentName,
                :gitProvider, :gitHostUrl, :gitRemoteUrl, :gitDefaultBranch,
                :gitAuthMode, :credentialRef, :checkoutPath,
                :sandboxType, :sandboxTemplateId, :sandboxImageRef, :sandboxComposeRef,
                :status, now(), now()
            )
            SQL,
            $this->toParams($project),
        );
    }

    public function update(AgentProject $project): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
            UPDATE agent_projects SET
                name               = :name,
                agent_name         = :agentName,
                git_provider       = :gitProvider,
                git_host_url       = :gitHostUrl,
                git_remote_url     = :gitRemoteUrl,
                git_default_branch = :gitDefaultBranch,
                git_auth_mode      = :gitAuthMode,
                credential_ref     = :credentialRef,
                checkout_path      = :checkoutPath,
                sandbox_type       = :sandboxType,
                sandbox_template_id = :sandboxTemplateId,
                sandbox_image_ref  = :sandboxImageRef,
                sandbox_compose_ref = :sandboxComposeRef,
                status             = :status,
                updated_at         = now()
            WHERE slug = :slug
            SQL,
            $this->toParams($project),
        );
    }

    public function findBySlug(string $slug): ?AgentProject
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM agent_projects WHERE slug = :slug',
            ['slug' => $slug],
        );

        return false === $row ? null : AgentProject::fromDatabaseRow($row);
    }

    public function findByAgentName(string $agentName): ?AgentProject
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM agent_projects WHERE agent_name = :agentName',
            ['agentName' => $agentName],
        );

        return false === $row ? null : AgentProject::fromDatabaseRow($row);
    }

    /**
     * @return list<AgentProject>
     */
    public function findAll(): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM agent_projects ORDER BY slug',
        );

        return array_map(AgentProject::fromDatabaseRow(...), $rows);
    }

    public function delete(string $slug): bool
    {
        $rows = $this->connection->executeStatement(
            'DELETE FROM agent_projects WHERE slug = :slug',
            ['slug' => $slug],
        );

        return $rows > 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function toParams(AgentProject $project): array
    {
        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
            'agentName' => $project->agentName,
            'gitProvider' => $project->gitProvider->value,
            'gitHostUrl' => $project->gitHostUrl,
            'gitRemoteUrl' => $project->gitRemoteUrl,
            'gitDefaultBranch' => $project->gitDefaultBranch,
            'gitAuthMode' => $project->gitAuthMode->value,
            'credentialRef' => $project->credentialRef,
            'checkoutPath' => $project->checkoutPath,
            'sandboxType' => $project->sandboxType->value,
            'sandboxTemplateId' => $project->sandboxTemplateId,
            'sandboxImageRef' => $project->sandboxImageRef,
            'sandboxComposeRef' => $project->sandboxComposeRef,
            'status' => $project->status->value,
        ];
    }
}
