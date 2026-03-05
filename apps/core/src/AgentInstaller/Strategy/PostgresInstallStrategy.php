<?php

declare(strict_types=1);

namespace App\AgentInstaller\Strategy;

use App\AgentInstaller\AgentInstallException;
use Doctrine\DBAL\Connection;

final class PostgresInstallStrategy implements InstallStrategyInterface
{
    private const IDENTIFIER_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function provision(array $storageConfig, string $agentName): array
    {
        $dbName = (string) ($storageConfig['db_name'] ?? '');
        $user = (string) ($storageConfig['user'] ?? '');
        $password = (string) ($storageConfig['password'] ?? '');

        $this->assertValidIdentifier($dbName, 'db_name');
        $this->assertValidIdentifier($user, 'user');

        $actions = [];

        if (!$this->roleExists($user)) {
            $this->connection->executeStatement(
                sprintf('CREATE USER %s WITH PASSWORD %s', $this->quoteIdentifier($user), $this->connection->quote($password)),
            );
            $actions[] = sprintf('created_user:%s', $user);
        }

        if (!$this->databaseExists($dbName)) {
            $this->connection->executeStatement(
                sprintf('CREATE DATABASE %s OWNER %s', $this->quoteIdentifier($dbName), $this->quoteIdentifier($user)),
            );
            $actions[] = sprintf('created_database:%s', $dbName);
        }

        $this->connection->executeStatement(
            sprintf('GRANT ALL PRIVILEGES ON DATABASE %s TO %s', $this->quoteIdentifier($dbName), $this->quoteIdentifier($user)),
        );

        return $actions;
    }

    public function isProvisioned(array $storageConfig): bool
    {
        $dbName = (string) ($storageConfig['db_name'] ?? '');
        $user = (string) ($storageConfig['user'] ?? '');

        return $this->roleExists($user) && $this->databaseExists($dbName);
    }

    private function roleExists(string $name): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT 1 FROM pg_roles WHERE rolname = :name',
            ['name' => $name],
        );

        return false !== $result;
    }

    private function databaseExists(string $name): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT 1 FROM pg_database WHERE datname = :name',
            ['name' => $name],
        );

        return false !== $result;
    }

    private function assertValidIdentifier(string $value, string $field): void
    {
        if (!preg_match(self::IDENTIFIER_PATTERN, $value)) {
            throw new AgentInstallException(sprintf('Invalid Postgres identifier for "%s": %s', $field, $value));
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return sprintf('"%s"', $identifier);
    }
}
