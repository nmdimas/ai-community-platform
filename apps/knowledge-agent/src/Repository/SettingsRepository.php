<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class SettingsRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function get(string $key, string $default = ''): string
    {
        $value = $this->connection->fetchOne(
            'SELECT value FROM agent_settings WHERE key = :key',
            ['key' => $key],
        );

        return false !== $value ? (string) $value : $default;
    }

    public function set(string $key, string $value): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO agent_settings (key, value, updated_at)
                VALUES (:key, :value, now())
                ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = now()
            SQL,
            ['key' => $key, 'value' => $value],
        );
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT key, value FROM agent_settings');

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['key']] = (string) $row['value'];
        }

        return $result;
    }
}
