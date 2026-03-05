<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add violations and health_check_failures columns to agent_registry for 4-state discovery status machine';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE agent_registry
                ADD COLUMN violations JSONB NOT NULL DEFAULT '[]',
                ADD COLUMN health_check_failures INTEGER NOT NULL DEFAULT 0
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE agent_registry
                DROP COLUMN violations,
                DROP COLUMN health_check_failures
        SQL);
    }
}
