<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed initial agent settings with default description and system prompt';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO agent_settings (key, value, updated_at)
            VALUES
                ('description', 'Knowledge base management and semantic search agent. Extracts structured knowledge from messages and provides semantic search capabilities.', now()),
                ('system_prompt', 'You are a knowledge management assistant. Extract key facts, definitions, and relationships from user messages. Organize information clearly and provide precise answers based on the knowledge base.', now())
            ON CONFLICT (key) DO NOTHING
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM agent_settings WHERE key IN ('description', 'system_prompt')");
    }
}
