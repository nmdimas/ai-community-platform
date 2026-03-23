<?php

declare(strict_types=1);

namespace App\AgentProject;

/**
 * Resolves a symbolic credential reference to its actual secret value at runtime.
 *
 * Credential references are stored in agent_projects.credential_ref as symbolic
 * pointers (e.g., "env:MY_TOKEN" or "vault:agent/hello/git-token"). This interface
 * decouples the resolution strategy from the domain model, keeping secrets out of
 * the database and task payloads.
 *
 * Supported reference schemes (Stage 1):
 *   - env:VAR_NAME  — resolved from environment variables
 *
 * Future schemes (Stage 2+):
 *   - vault:path/to/secret — resolved from HashiCorp Vault or similar
 */
interface CredentialResolverInterface
{
    /**
     * Resolve a credential reference to its secret value.
     *
     * Returns null if the reference cannot be resolved (e.g., env var not set,
     * vault path not found, or unsupported scheme).
     */
    public function resolve(string $ref): ?string;
}
