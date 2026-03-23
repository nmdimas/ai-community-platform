<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create scheduler_job_logs table for execution history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE scheduler_job_logs (
                id UUID NOT NULL DEFAULT gen_random_uuid(),
                job_id UUID,
                agent_name VARCHAR(64) NOT NULL,
                skill_id VARCHAR(128) NOT NULL,
                job_name VARCHAR(128) NOT NULL,
                payload_sent JSONB NOT NULL DEFAULT '{}',
                response_received JSONB,
                status VARCHAR(32) NOT NULL,
                error_message TEXT,
                started_at TIMESTAMPTZ NOT NULL,
                finished_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id),
                CONSTRAINT fk_scheduler_job_logs_job
                    FOREIGN KEY (job_id) REFERENCES scheduled_jobs(id)
                    ON DELETE SET NULL
            )
            SQL);

        $this->addSql('CREATE INDEX idx_scheduler_job_logs_job_created ON scheduler_job_logs (job_id, created_at DESC)');
        $this->addSql('CREATE INDEX idx_scheduler_job_logs_created ON scheduler_job_logs (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS scheduler_job_logs');
    }
}
