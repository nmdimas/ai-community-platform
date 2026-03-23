<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_users table and seed default admin user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE admin_users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSONB NOT NULL DEFAULT '["ROLE_ADMIN"]',
                CONSTRAINT admin_users_username_unique UNIQUE (username)
            )
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO admin_users (username, password, roles)
            VALUES ('admin', '$2y$12$ksBapX7FlfFlClRm5sOtCOZvI6bpdtQ9dOcB8ItH4NpX4V9i.9DMK', '["ROLE_ADMIN"]')
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admin_users');
    }
}
