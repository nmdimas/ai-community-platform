# Design: Development Workflow Improvements

## Context
The platform needs a more rigorous development workflow to ensure code quality, prevent database corruption, and maintain consistency across multiple agents and contributors. Current ad-hoc practices lead to inconsistent quality and potential production issues.

## Goals / Non-Goals

### Goals:
- Enforce database changes through migrations only
- Require test-first development for all features
- Ensure all code passes quality checks before PR
- Maintain continuous integration within active proposals
- Provide clear sandbox patterns for isolated development
- Create reproducible and auditable development process

### Non-Goals:
- Automate the entire CI/CD pipeline (future work)
- Implement complex branching strategies
- Add new testing frameworks
- Change existing technology stack

## Decisions

### Decision: Mandatory Migration Workflow
**Choice**: All database changes must go through migrations
**Rationale**:
- Prevents accidental schema corruption
- Enables rollback capabilities
- Maintains consistency across environments
- Creates audit trail of schema evolution

**Alternatives considered**:
- Manual schema updates: Rejected due to error-prone nature
- Schema sync tools: Rejected as they can cause data loss

### Decision: Test-Driven Development Requirement
**Choice**: Tests must be written before implementation
**Rationale**:
- Forces clear requirement understanding
- Catches issues early in development
- Provides regression protection
- Documents expected behavior

**Alternatives considered**:
- Test-after approach: Rejected as tests often get skipped
- No testing requirement: Rejected due to quality concerns

### Decision: Strict Quality Gates
**Choice**: All checks must pass before PR creation
**Rationale**:
- Prevents broken code from entering review
- Reduces reviewer burden
- Maintains consistent code quality
- Catches issues before they compound

**Alternatives considered**:
- Allow PRs with failing tests: Rejected as it normalizes broken builds
- Post-merge fixes: Rejected due to production risk

### Decision: Continuous Push Requirement
**Choice**: Active proposal work must be pushed regularly
**Rationale**:
- Enables collaboration and early feedback
- Prevents work loss
- Shows progress transparency
- Facilitates handoffs if needed

**Alternatives considered**:
- Large batch commits: Rejected due to merge conflicts
- Local-only development: Rejected due to collaboration issues

## Implementation Pattern

### Workflow Enforcement
```yaml
# .github/workflows/pr-checks.yml or .gitlab-ci.yml
stages:
  - validate
  - test
  - quality

validate:
  script:
    - openspec validate --strict
    - php bin/console doctrine:schema:validate

test:
  script:
    - vendor/bin/codecept run

quality:
  script:
    - vendor/bin/phpstan analyse
    - vendor/bin/php-cs-fixer check
```

### Migration Template
```php
<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionYYYYMMDDHHMMSS extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OpenSpec: change-id - Description';
    }

    public function up(Schema $schema): void
    {
        // Forward migration
    }

    public function down(Schema $schema): void
    {
        // Rollback migration
    }
}
```

### Sandbox Docker Configuration
```yaml
# docker-compose.sandbox.yml
services:
  agent-sandbox:
    build: ./agents/${AGENT_NAME}
    environment:
      - DB_PREFIX=${AGENT_NAME}_
      - APP_ENV=sandbox
    volumes:
      - ./agents/${AGENT_NAME}:/app
    networks:
      - sandbox_network
```

## Risks / Trade-offs

### Risk: Increased Development Time
**Mitigation**:
- Provide templates and generators
- Automate repetitive tasks
- Clear documentation and examples

### Risk: Developer Resistance
**Mitigation**:
- Gradual rollout with training
- Show value through fewer production issues
- Tooling to make compliance easy

### Trade-off: Flexibility vs Safety
**Decision**: Choose safety
**Rationale**: Platform stability critical for user trust

### Trade-off: Speed vs Quality
**Decision**: Quality first
**Rationale**: Technical debt compounds quickly in distributed systems

## Migration Plan

### Phase 1: Documentation (Completed)
- Create workflow guidelines
- Update AGENTS.md
- Document best practices

### Phase 2: Tooling
- Add pre-commit hooks
- Create migration templates
- Set up CI pipeline

### Phase 3: Enforcement
- Enable quality gates in CI
- Require PR checks
- Monitor compliance

### Rollback Strategy
If workflow proves too restrictive:
1. Identify specific pain points
2. Adjust requirements while maintaining core safety
3. Never remove migration or testing requirements

## Open Questions

1. Should we add automated dependency updates to the workflow?
2. How to handle emergency hotfixes that need expedited process?
3. Should we implement feature flags for safer deployments?

## Success Metrics

- Zero direct database modifications
- 100% of new features have tests
- All PRs pass quality checks on first review
- Reduced production incidents by 50%
- Developer satisfaction with clear process