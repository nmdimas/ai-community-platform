<?php

declare(strict_types=1);

namespace App\AgentProject;

/**
 * Resolves credential references of the form "env:VAR_NAME" from environment variables.
 *
 * This is the Stage 1 implementation of CredentialResolverInterface. It handles
 * the "env:" scheme only. References with unsupported schemes return null.
 *
 * Example:
 *   resolve("env:HELLO_AGENT_GIT_TOKEN") → value of $_ENV['HELLO_AGENT_GIT_TOKEN']
 *   resolve("vault:agent/hello/token")   → null (unsupported in Stage 1)
 */
final class EnvCredentialResolver implements CredentialResolverInterface
{
    private const ENV_SCHEME = 'env:';

    public function resolve(string $ref): ?string
    {
        if (!str_starts_with($ref, self::ENV_SCHEME)) {
            return null;
        }

        $varName = substr($ref, \strlen(self::ENV_SCHEME));

        if ('' === $varName) {
            return null;
        }

        $value = getenv($varName);

        return false !== $value && '' !== $value ? $value : null;
    }
}
