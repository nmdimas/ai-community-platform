<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create agent_registry table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE agent_registry (
                id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name          VARCHAR(64) NOT NULL,
                version       VARCHAR(32) NOT NULL,
                manifest      JSONB NOT NULL,
                config        JSONB NOT NULL DEFAULT '{}',
                enabled       BOOLEAN NOT NULL DEFAULT FALSE,
                registered_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                updated_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
                enabled_at    TIMESTAMPTZ,
                disabled_at   TIMESTAMPTZ,
                enabled_by    VARCHAR(128),
                health_status VARCHAR(32) NOT NULL DEFAULT 'unknown',
                CONSTRAINT agent_registry_name_unique UNIQUE (name)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agent_registry');
    }
}
