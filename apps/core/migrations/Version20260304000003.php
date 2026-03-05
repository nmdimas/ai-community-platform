<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create agent_registry_audit table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE agent_registry_audit (
                id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                agent_name VARCHAR(64) NOT NULL,
                action     VARCHAR(32) NOT NULL,
                actor      VARCHAR(128),
                payload    JSONB NOT NULL DEFAULT '{}',
                created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agent_registry_audit');
    }
}
