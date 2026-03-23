<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create agent_projects table for Stage 1 Agent Project domain model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE agent_projects (
                id                  UUID        NOT NULL DEFAULT gen_random_uuid(),
                slug                VARCHAR(128) NOT NULL,
                name                VARCHAR(255) NOT NULL,
                agent_name          VARCHAR(128) NULL,
                git_provider        VARCHAR(32)  NOT NULL,
                git_host_url        VARCHAR(512) NOT NULL,
                git_remote_url      VARCHAR(1024) NOT NULL,
                git_default_branch  VARCHAR(128) NOT NULL DEFAULT 'main',
                git_auth_mode       VARCHAR(32)  NOT NULL DEFAULT 'none',
                credential_ref      VARCHAR(512) NULL,
                checkout_path       VARCHAR(512) NOT NULL,
                sandbox_type        VARCHAR(32)  NOT NULL,
                sandbox_template_id VARCHAR(128) NULL,
                sandbox_image_ref   VARCHAR(512) NULL,
                sandbox_compose_ref VARCHAR(256) NULL,
                status              VARCHAR(32)  NOT NULL DEFAULT 'draft',
                created_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
                updated_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
                PRIMARY KEY (id),
                CONSTRAINT uq_agent_projects_slug UNIQUE (slug)
            )
            SQL);

        $this->addSql('CREATE INDEX idx_agent_projects_agent_name ON agent_projects (agent_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS agent_projects');
    }
}
