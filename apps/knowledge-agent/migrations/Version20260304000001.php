<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create processed_chunks table for knowledge worker deduplication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS processed_chunks (
                id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                chunk_hash  VARCHAR(64) NOT NULL,
                status      VARCHAR(32) NOT NULL DEFAULT 'pending',
                attempt_count INT NOT NULL DEFAULT 0,
                processed_at TIMESTAMPTZ,
                created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
                CONSTRAINT processed_chunks_hash_unique UNIQUE (chunk_hash)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS processed_chunks');
    }
}
