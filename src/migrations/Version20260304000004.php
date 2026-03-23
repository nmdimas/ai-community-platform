<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create agent_invocation_audit table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS agent_invocation_audit (
                id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                tool        VARCHAR(128) NOT NULL,
                agent       VARCHAR(64)  NOT NULL,
                trace_id    VARCHAR(128),
                request_id  VARCHAR(128),
                duration_ms INT,
                status      VARCHAR(32)  NOT NULL,
                actor       VARCHAR(128) NOT NULL DEFAULT 'openclaw',
                created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
            )
        SQL);

        $this->addSql('CREATE INDEX idx_agent_invocation_audit_agent ON agent_invocation_audit (agent)');
        $this->addSql('CREATE INDEX idx_agent_invocation_audit_tool ON agent_invocation_audit (tool)');
        $this->addSql('CREATE INDEX idx_agent_invocation_audit_created_at ON agent_invocation_audit (created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS agent_invocation_audit');
    }
}
