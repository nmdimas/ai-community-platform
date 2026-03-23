<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create telegram_bots and telegram_chats tables for Telegram bot integration';
    }

    public function up(Schema $schema): void
    {
        // Create telegram_bots table
        $this->addSql(<<<'SQL'
            CREATE TABLE telegram_bots (
                id                      UUID            NOT NULL DEFAULT gen_random_uuid(),
                bot_username            VARCHAR(255)    NOT NULL,
                bot_token_encrypted     TEXT            NOT NULL,
                webhook_secret          VARCHAR(255)    NULL,
                community_id            VARCHAR(255)    NULL,
                privacy_mode            VARCHAR(50)     NOT NULL DEFAULT 'enabled',
                polling_mode            BOOLEAN         NOT NULL DEFAULT FALSE,
                role_overrides          JSONB           NULL,
                config                  JSONB           NULL,
                enabled                 BOOLEAN         NOT NULL DEFAULT TRUE,
                last_update_id          BIGINT          NULL,
                webhook_url             VARCHAR(1024)   NULL,
                created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        SQL);

        // Add unique index on bot_username
        $this->addSql('CREATE UNIQUE INDEX idx_telegram_bots_username ON telegram_bots (bot_username)');

        // Add index on enabled for filtering active bots
        $this->addSql('CREATE INDEX idx_telegram_bots_enabled ON telegram_bots (enabled)');

        // Create telegram_chats table
        $this->addSql(<<<'SQL'
            CREATE TABLE telegram_chats (
                id                  UUID            NOT NULL DEFAULT gen_random_uuid(),
                bot_id              UUID            NOT NULL,
                chat_id             BIGINT          NOT NULL,
                title               VARCHAR(255)    NULL,
                type                VARCHAR(50)     NOT NULL,
                has_threads         BOOLEAN         NOT NULL DEFAULT FALSE,
                member_count        INTEGER         NULL,
                joined_at           TIMESTAMP       NULL,
                left_at             TIMESTAMP       NULL,
                metadata            JSONB           NULL,
                last_message_at     TIMESTAMP       NULL,
                created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                CONSTRAINT fk_telegram_chat_bot
                    FOREIGN KEY (bot_id)
                    REFERENCES telegram_bots (id)
                    ON DELETE CASCADE
            )
        SQL);

        // Add unique index on bot_id + chat_id combination
        $this->addSql('CREATE UNIQUE INDEX idx_telegram_chats_bot_chat ON telegram_chats (bot_id, chat_id)');

        // Add index on bot_id for querying chats by bot
        $this->addSql('CREATE INDEX idx_telegram_chats_bot ON telegram_chats (bot_id)');

        // Add index on last_message_at for activity queries
        $this->addSql('CREATE INDEX idx_telegram_chats_activity ON telegram_chats (last_message_at)');

        // Add index on left_at for filtering active chats
        $this->addSql('CREATE INDEX idx_telegram_chats_left ON telegram_chats (left_at) WHERE left_at IS NULL');

        // Create update trigger for updated_at columns
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ language 'plpgsql';
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TRIGGER update_telegram_bots_updated_at
            BEFORE UPDATE ON telegram_bots
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TRIGGER update_telegram_chats_updated_at
            BEFORE UPDATE ON telegram_chats
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Drop triggers
        $this->addSql('DROP TRIGGER IF EXISTS update_telegram_chats_updated_at ON telegram_chats');
        $this->addSql('DROP TRIGGER IF EXISTS update_telegram_bots_updated_at ON telegram_bots');
        $this->addSql('DROP FUNCTION IF EXISTS update_updated_at_column()');

        // Drop tables
        $this->addSql('DROP TABLE IF EXISTS telegram_chats');
        $this->addSql('DROP TABLE IF EXISTS telegram_bots');
    }
}