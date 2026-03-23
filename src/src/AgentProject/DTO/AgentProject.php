<?php

declare(strict_types=1);

namespace App\AgentProject\DTO;

use App\AgentProject\GitAuthMode;
use App\AgentProject\GitProvider;
use App\AgentProject\ProjectStatus;
use App\AgentProject\SandboxType;

/**
 * An agent project record from the agent_projects database table.
 *
 * Captures repository metadata, credential references, checkout paths,
 * and sandbox configuration for a managed agent project. Secrets are
 * never stored here — only symbolic credential references (e.g., env:VAR).
 */
final readonly class AgentProject
{
    public function __construct(
        /** @var string UUID primary key */
        public string $id,
        /** @var string Kebab-case project identifier (unique) */
        public string $slug,
        /** @var string Human-readable display name */
        public string $name,
        /** @var string|null Soft link to agent_registry.name (no FK constraint) */
        public ?string $agentName,
        /** @var GitProvider Git hosting provider */
        public GitProvider $gitProvider,
        /** @var string Git host base URL (e.g., https://github.com) */
        public string $gitHostUrl,
        /** @var string Git clone URL */
        public string $gitRemoteUrl,
        /** @var string Default branch name */
        public string $gitDefaultBranch,
        /** @var GitAuthMode Authentication mode for the repository */
        public GitAuthMode $gitAuthMode,
        /** @var string|null Symbolic credential reference (env:VAR or vault:path) — never a raw secret */
        public ?string $credentialRef,
        /** @var string Local checkout path (e.g., projects/my-agent/) */
        public string $checkoutPath,
        /** @var SandboxType Runtime sandbox strategy */
        public SandboxType $sandboxType,
        /** @var string|null Platform sandbox template ID (e.g., php-symfony-agent) */
        public ?string $sandboxTemplateId,
        /** @var string|null Docker image reference or Dockerfile path */
        public ?string $sandboxImageRef,
        /** @var string|null Compose file and service name reference */
        public ?string $sandboxComposeRef,
        /** @var ProjectStatus Project lifecycle status */
        public ProjectStatus $status,
        /** @var \DateTimeImmutable|null When the project record was created */
        public ?\DateTimeImmutable $createdAt = null,
        /** @var \DateTimeImmutable|null When the project record was last updated */
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * Hydrate from a database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: (string) ($row['id'] ?? ''),
            slug: (string) ($row['slug'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            agentName: isset($row['agent_name']) && '' !== $row['agent_name'] ? (string) $row['agent_name'] : null,
            gitProvider: GitProvider::tryFrom((string) ($row['git_provider'] ?? '')) ?? GitProvider::GitHub,
            gitHostUrl: (string) ($row['git_host_url'] ?? ''),
            gitRemoteUrl: (string) ($row['git_remote_url'] ?? ''),
            gitDefaultBranch: (string) ($row['git_default_branch'] ?? 'main'),
            gitAuthMode: GitAuthMode::tryFrom((string) ($row['git_auth_mode'] ?? '')) ?? GitAuthMode::None,
            credentialRef: isset($row['credential_ref']) && '' !== $row['credential_ref'] ? (string) $row['credential_ref'] : null,
            checkoutPath: (string) ($row['checkout_path'] ?? ''),
            sandboxType: SandboxType::tryFrom((string) ($row['sandbox_type'] ?? '')) ?? SandboxType::Template,
            sandboxTemplateId: isset($row['sandbox_template_id']) && '' !== $row['sandbox_template_id'] ? (string) $row['sandbox_template_id'] : null,
            sandboxImageRef: isset($row['sandbox_image_ref']) && '' !== $row['sandbox_image_ref'] ? (string) $row['sandbox_image_ref'] : null,
            sandboxComposeRef: isset($row['sandbox_compose_ref']) && '' !== $row['sandbox_compose_ref'] ? (string) $row['sandbox_compose_ref'] : null,
            status: ProjectStatus::tryFrom((string) ($row['status'] ?? '')) ?? ProjectStatus::Draft,
            createdAt: self::parseDateTime($row['created_at'] ?? null),
            updatedAt: self::parseDateTime($row['updated_at'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'agent_name' => $this->agentName,
            'git_provider' => $this->gitProvider->value,
            'git_host_url' => $this->gitHostUrl,
            'git_remote_url' => $this->gitRemoteUrl,
            'git_default_branch' => $this->gitDefaultBranch,
            'git_auth_mode' => $this->gitAuthMode->value,
            'credential_ref' => $this->credentialRef,
            'checkout_path' => $this->checkoutPath,
            'sandbox_type' => $this->sandboxType->value,
            'sandbox_template_id' => $this->sandboxTemplateId,
            'sandbox_image_ref' => $this->sandboxImageRef,
            'sandbox_compose_ref' => $this->sandboxComposeRef,
            'status' => $this->status->value,
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    private static function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if (!\is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
