<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add installed_at column to agent_registry for storage provisioning tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE agent_registry
                ADD COLUMN installed_at TIMESTAMPTZ NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE agent_registry
                DROP COLUMN installed_at
        SQL);
    }
}
