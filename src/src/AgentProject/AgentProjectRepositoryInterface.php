<?php

declare(strict_types=1);

namespace App\AgentProject;

use App\AgentProject\DTO\AgentProject;

interface AgentProjectRepositoryInterface
{
    /**
     * Persist a new agent project record.
     */
    public function create(AgentProject $project): void;

    /**
     * Update an existing agent project record.
     */
    public function update(AgentProject $project): void;

    /**
     * Find a project by its unique slug.
     */
    public function findBySlug(string $slug): ?AgentProject;

    /**
     * Find a project by its linked agent name (soft link to agent_registry.name).
     */
    public function findByAgentName(string $agentName): ?AgentProject;

    /**
     * Return all agent project records ordered by slug.
     *
     * @return list<AgentProject>
     */
    public function findAll(): array;

    /**
     * Delete a project by slug. Returns true if a record was deleted.
     */
    public function delete(string $slug): bool;
}
