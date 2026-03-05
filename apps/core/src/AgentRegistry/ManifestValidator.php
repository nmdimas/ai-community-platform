<?php

declare(strict_types=1);

namespace App\AgentRegistry;

final class ManifestValidator
{
    private const REQUIRED_FIELDS = [
        'name',
        'version',
        'description',
        'permissions',
        'commands',
        'events',
        'a2a_endpoint',
    ];

    private const NAME_PATTERN = '/^[a-z][a-z0-9-]*$/';
    private const VERSION_PATTERN = '/^\d+\.\d+\.\d+$/';
    private const IDENTIFIER_PATTERN = '/^[a-z][a-z0-9_]*$/';
    private const COLLECTION_PATTERN = '/^[a-z][a-z0-9_-]*$/';

    /**
     * @param array<string, mixed> $manifest
     *
     * @return list<string> list of validation error messages, empty if valid
     */
    public function validate(array $manifest): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $manifest)) {
                $errors[] = sprintf('Missing required field: %s', $field);
            }
        }

        if ([] !== $errors) {
            return $errors;
        }

        if (!is_string($manifest['name']) || !preg_match(self::NAME_PATTERN, $manifest['name'])) {
            $errors[] = 'Field "name" must be a non-empty kebab-case string (e.g. knowledge-base)';
        }

        if (!is_string($manifest['version']) || !preg_match(self::VERSION_PATTERN, $manifest['version'])) {
            $errors[] = 'Field "version" must be a semver string (e.g. 1.0.0)';
        }

        if (!is_string($manifest['description']) || '' === $manifest['description']) {
            $errors[] = 'Field "description" must be a non-empty string';
        }

        foreach (['permissions', 'commands', 'events'] as $arrayField) {
            if (!is_array($manifest[$arrayField])) {
                $errors[] = sprintf('Field "%s" must be an array', $arrayField);
            } elseif (!$this->isStringArray($manifest[$arrayField])) {
                $errors[] = sprintf('Field "%s" must be an array of strings', $arrayField);
            }
        }

        if (!is_string($manifest['a2a_endpoint']) || !filter_var($manifest['a2a_endpoint'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Field "a2a_endpoint" must be a valid URL';
        }

        if (array_key_exists('config_schema', $manifest) && !is_array($manifest['config_schema'])) {
            $errors[] = 'Field "config_schema" must be an object';
        }

        if (array_key_exists('capabilities', $manifest)) {
            if (!is_array($manifest['capabilities'])) {
                $errors[] = 'Field "capabilities" must be an array';
            } elseif (!$this->isStringArray($manifest['capabilities'])) {
                $errors[] = 'Field "capabilities" must be an array of strings';
            }
        }

        if (array_key_exists('capability_schemas', $manifest) && !is_array($manifest['capability_schemas'])) {
            $errors[] = 'Field "capability_schemas" must be an object';
        }

        if (array_key_exists('health_url', $manifest)) {
            if (!is_string($manifest['health_url']) || !filter_var($manifest['health_url'], FILTER_VALIDATE_URL)) {
                $errors[] = 'Field "health_url" must be a valid URL';
            }
        }

        if (array_key_exists('storage', $manifest)) {
            if (!is_array($manifest['storage'])) {
                $errors[] = 'Field "storage" must be an object';
            } else {
                $errors = array_merge($errors, $this->validateStorage($manifest['storage']));
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $storage
     *
     * @return list<string>
     */
    private function validateStorage(array $storage): array
    {
        $errors = [];

        if (array_key_exists('postgres', $storage)) {
            if (!is_array($storage['postgres'])) {
                $errors[] = 'Field "storage.postgres" must be an object';
            } else {
                $errors = array_merge($errors, $this->validatePostgresStorage($storage['postgres']));
            }
        }

        if (array_key_exists('redis', $storage)) {
            if (!is_array($storage['redis'])) {
                $errors[] = 'Field "storage.redis" must be an object';
            } else {
                $errors = array_merge($errors, $this->validateRedisStorage($storage['redis']));
            }
        }

        if (array_key_exists('opensearch', $storage)) {
            if (!is_array($storage['opensearch'])) {
                $errors[] = 'Field "storage.opensearch" must be an object';
            } else {
                $errors = array_merge($errors, $this->validateOpenSearchStorage($storage['opensearch']));
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $postgres
     *
     * @return list<string>
     */
    private function validatePostgresStorage(array $postgres): array
    {
        $errors = [];

        foreach (['db_name', 'user', 'password'] as $field) {
            if (!array_key_exists($field, $postgres)) {
                $errors[] = sprintf('Missing required field: storage.postgres.%s', $field);
            } elseif (!is_string($postgres[$field]) || '' === $postgres[$field]) {
                $errors[] = sprintf('Field "storage.postgres.%s" must be a non-empty string', $field);
            }
        }

        if ([] !== $errors) {
            return $errors;
        }

        foreach (['db_name', 'user'] as $field) {
            if (!preg_match(self::IDENTIFIER_PATTERN, (string) $postgres[$field])) {
                $errors[] = sprintf('Field "storage.postgres.%s" must be a valid identifier (lowercase letters, digits, underscores)', $field);
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $redis
     *
     * @return list<string>
     */
    private function validateRedisStorage(array $redis): array
    {
        $errors = [];

        if (!array_key_exists('db_number', $redis)) {
            $errors[] = 'Missing required field: storage.redis.db_number';
        } elseif (!is_int($redis['db_number'])) {
            $errors[] = 'Field "storage.redis.db_number" must be an integer';
        } elseif ($redis['db_number'] < 0 || $redis['db_number'] > 15) {
            $errors[] = 'Field "storage.redis.db_number" must be between 0 and 15';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $opensearch
     *
     * @return list<string>
     */
    private function validateOpenSearchStorage(array $opensearch): array
    {
        $errors = [];

        if (!array_key_exists('collections', $opensearch)) {
            $errors[] = 'Missing required field: storage.opensearch.collections';

            return $errors;
        }

        if (!is_array($opensearch['collections'])) {
            $errors[] = 'Field "storage.opensearch.collections" must be an array';

            return $errors;
        }

        if ([] === $opensearch['collections']) {
            $errors[] = 'Field "storage.opensearch.collections" must not be empty';

            return $errors;
        }

        foreach ($opensearch['collections'] as $i => $collection) {
            if (!is_string($collection) || !preg_match(self::COLLECTION_PATTERN, $collection)) {
                $errors[] = sprintf('Field "storage.opensearch.collections[%d]" must be a valid identifier (lowercase letters, digits, underscores, hyphens)', $i);
            }
        }

        return $errors;
    }

    /**
     * @param mixed[] $array
     */
    private function isStringArray(array $array): bool
    {
        foreach ($array as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        return true;
    }
}
