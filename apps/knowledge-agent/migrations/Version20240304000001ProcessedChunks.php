<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240304000001ProcessedChunks extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create processed_chunks table for idempotent chunk processing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE processed_chunks (
                id BIGSERIAL PRIMARY KEY,
                chunk_hash VARCHAR(64) NOT NULL UNIQUE,
                status VARCHAR(20) NOT NULL DEFAULT 'processing',
                attempt_count INTEGER NOT NULL DEFAULT 0,
                processed_at TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now()
            )
        SQL);

        $this->addSql('CREATE INDEX idx_processed_chunks_hash ON processed_chunks (chunk_hash)');
        $this->addSql('CREATE INDEX idx_processed_chunks_status ON processed_chunks (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE processed_chunks');
    }
}
