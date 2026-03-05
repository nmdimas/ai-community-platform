<?php

declare(strict_types=1);

namespace App\Tests\Unit\AgentRegistry;

use App\AgentRegistry\ManifestValidator;
use Codeception\Test\Unit;

/**
 * Validates agent manifest structure and required fields.
 *
 * @see docs/specs/a2a-protocol.md (Section: Agent Manifest payload and structure)
 */
final class ManifestValidatorTest extends Unit
{
    private ManifestValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ManifestValidator();
    }

    public function testValidManifestPassesValidation(): void
    {
        $manifest = [
            'name' => 'knowledge-base',
            'version' => '0.1.0',
            'description' => 'Extracts structured knowledge from chat messages.',
            'permissions' => ['moderator', 'admin'],
            'commands' => ['/wiki'],
            'events' => ['message.created'],
            'a2a_endpoint' => 'http://knowledge-agent/a2a',
        ];

        $errors = $this->validator->validate($manifest);

        $this->assertSame([], $errors);
    }

    public function testMissingRequiredFieldsReturnsErrors(): void
    {
        $errors = $this->validator->validate([]);

        foreach (['name', 'version', 'description', 'permissions', 'commands', 'events', 'a2a_endpoint'] as $field) {
            $this->assertNotEmpty(
                array_filter($errors, static fn (string $e) => str_contains($e, $field)),
                sprintf('Expected error for missing field "%s"', $field),
            );
        }
    }

    public function testInvalidNameFormatReturnsError(): void
    {
        $manifest = $this->validManifest(['name' => 'Invalid Name!']);

        $errors = $this->validator->validate($manifest);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('name', implode(' ', $errors));
    }

    public function testInvalidVersionFormatReturnsError(): void
    {
        $manifest = $this->validManifest(['version' => 'v1.2']);

        $errors = $this->validator->validate($manifest);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('version', implode(' ', $errors));
    }

    public function testInvalidA2aEndpointReturnsError(): void
    {
        $manifest = $this->validManifest(['a2a_endpoint' => 'not-a-url']);

        $errors = $this->validator->validate($manifest);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('a2a_endpoint', implode(' ', $errors));
    }

    public function testInvalidConfigSchemaTypeReturnsError(): void
    {
        $manifest = $this->validManifest(['config_schema' => 'should-be-object']);

        $errors = $this->validator->validate($manifest);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('config_schema', implode(' ', $errors));
    }

    public function testOptionalFieldsAreIgnoredWhenAbsent(): void
    {
        $errors = $this->validator->validate($this->validManifest());

        $this->assertSame([], $errors);
    }

    public function testValidOptionalFieldsPassValidation(): void
    {
        $manifest = $this->validManifest([
            'config_schema' => ['type' => 'object', 'properties' => []],
            'capabilities' => ['knowledge_search'],
            'health_url' => 'http://knowledge-agent/health',
            'admin_url' => '/admin/knowledge',
        ]);

        $errors = $this->validator->validate($manifest);

        $this->assertSame([], $errors);
    }

    public function testValidStorageSectionPassesValidation(): void
    {
        $manifest = $this->validManifest([
            'storage' => [
                'postgres' => ['db_name' => 'my_agent', 'user' => 'my_agent', 'password' => 'my_agent'],
                'redis' => ['db_number' => 1],
                'opensearch' => ['collections' => ['chunks', 'pages']],
            ],
        ]);

        $this->assertSame([], $this->validator->validate($manifest));
    }

    public function testManifestWithoutStorageSectionPassesValidation(): void
    {
        $this->assertSame([], $this->validator->validate($this->validManifest()));
    }

    public function testStorageSectionMustBeObject(): void
    {
        $errors = $this->validator->validate($this->validManifest(['storage' => 'not-an-object']));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('storage', implode(' ', $errors));
    }

    public function testStoragePostgresRequiresAllFields(): void
    {
        foreach (['db_name', 'user', 'password'] as $field) {
            $postgres = ['db_name' => 'db', 'user' => 'usr', 'password' => 'pwd'];
            unset($postgres[$field]);

            $errors = $this->validator->validate($this->validManifest([
                'storage' => ['postgres' => $postgres],
            ]));

            $this->assertNotEmpty($errors, sprintf('Expected error for missing postgres.%s', $field));
        }
    }

    public function testStoragePostgresDbNameMustBeValidIdentifier(): void
    {
        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['postgres' => ['db_name' => 'INVALID-NAME!', 'user' => 'usr', 'password' => 'pwd']],
        ]));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('db_name', implode(' ', $errors));
    }

    public function testStoragePostgresUserMustBeValidIdentifier(): void
    {
        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['postgres' => ['db_name' => 'db', 'user' => 'BAD-USER', 'password' => 'pwd']],
        ]));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('user', implode(' ', $errors));
    }

    public function testStorageRedisDbNumberMustBeInRange(): void
    {
        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['redis' => ['db_number' => 16]],
        ]));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('db_number', implode(' ', $errors));

        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['redis' => ['db_number' => -1]],
        ]));

        $this->assertNotEmpty($errors);
    }

    public function testStorageRedisDbNumberMustBeInteger(): void
    {
        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['redis' => ['db_number' => 'not-int']],
        ]));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('db_number', implode(' ', $errors));
    }

    public function testStorageOpenSearchCollectionsMustBeNonEmpty(): void
    {
        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['opensearch' => ['collections' => []]],
        ]));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('collections', implode(' ', $errors));
    }

    public function testStorageOpenSearchCollectionNamesMustBeValid(): void
    {
        $errors = $this->validator->validate($this->validManifest([
            'storage' => ['opensearch' => ['collections' => ['INVALID!']]],
        ]));

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('collections', implode(' ', $errors));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validManifest(array $overrides = []): array
    {
        return array_merge([
            'name' => 'test-agent',
            'version' => '1.0.0',
            'description' => 'Test agent',
            'permissions' => ['admin'],
            'commands' => [],
            'events' => ['message.created'],
            'a2a_endpoint' => 'http://test-agent/a2a',
        ], $overrides);
    }
}
