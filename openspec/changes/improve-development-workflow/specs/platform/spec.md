# Platform Development Process Specification

## ADDED Requirements

### Requirement: Database Migration Workflow
All database schema changes SHALL be implemented exclusively through migrations, with no direct database modifications allowed.

#### Scenario: Creating a new table
- **WHEN** a developer needs to add a new database table
- **THEN** they must create a migration using `php bin/console make:migration`
- **AND** review the generated SQL before applying
- **AND** test the migration in development environment
- **AND** ensure migration is reversible

#### Scenario: Modifying existing schema
- **WHEN** a developer needs to modify an existing table
- **THEN** they must create a migration for the change
- **AND** document breaking changes in migration comments
- **AND** provide rollback strategy for complex changes

### Requirement: Test-Driven Development
Every new feature SHALL have automated tests written before implementation begins.

#### Scenario: Implementing new feature
- **WHEN** a developer starts implementing a new feature
- **THEN** they must first write test scenarios
- **AND** tests must cover happy path and edge cases
- **AND** tests must include error handling validation
- **AND** implementation can only begin after tests are defined

#### Scenario: Test hierarchy compliance
- **WHEN** writing tests for a feature
- **THEN** unit tests must cover business logic
- **AND** integration tests must cover service interactions
- **AND** functional tests must cover user flows
- **AND** E2E tests must cover critical paths

### Requirement: Quality Gates
All quality checks SHALL pass before a pull request can be created.

#### Scenario: Pre-PR quality validation
- **WHEN** a developer wants to create a pull request
- **THEN** PHPStan must show zero errors at level 8
- **AND** PHP CS Fixer must show no style violations
- **AND** all Codeception tests must pass
- **AND** convention compliance tests must pass if applicable
- **AND** E2E tests must pass if applicable

#### Scenario: Failed quality check
- **WHEN** any quality check fails
- **THEN** the developer must fix the issues
- **AND** re-run all checks
- **AND** cannot create PR until all checks pass

### Requirement: Continuous Integration Within Active Proposals
All changes within an active OpenSpec proposal SHALL be continuously committed and pushed.

#### Scenario: Working on active proposal
- **WHEN** a developer makes changes for an active proposal
- **THEN** they must commit changes frequently with descriptive messages
- **AND** push to feature branch regularly
- **AND** keep pull request updated with progress
- **AND** reference the proposal in commit messages

#### Scenario: Long-running implementation
- **WHEN** implementation spans multiple days
- **THEN** work in progress must be pushed daily
- **AND** task checklist must be updated to reflect progress
- **AND** PR description must show tasks completed ratio

### Requirement: Pull Request Specification Reference
Every pull request SHALL reference the OpenSpec proposal it implements.

#### Scenario: Creating PR for feature
- **WHEN** a developer creates a pull request
- **THEN** the PR title must be clear and action-oriented
- **AND** the PR body must reference the change ID
- **AND** the PR must link to the proposal document
- **AND** the PR must show task completion status
- **AND** the PR must confirm all tests pass

### Requirement: Sandbox Development Environment
Long-running tasks and agent development SHALL use isolated sandbox environments.

#### Scenario: Developing new agent
- **WHEN** a developer creates a new agent
- **THEN** they must use a separate Docker container
- **AND** use isolated database schema with prefix
- **AND** maintain separate configuration files
- **AND** implement independent test suite

#### Scenario: Running long task
- **WHEN** executing a task longer than 10 minutes
- **THEN** the task must run in background
- **AND** implement progress tracking
- **AND** provide timeout handling
- **AND** allow graceful cancellation

## MODIFIED Requirements

### Requirement: OpenSpec Implementation Workflow
The implementation stage SHALL include explicit steps for migrations, testing, and continuous integration.

#### Scenario: Following implementation workflow
- **WHEN** implementing an approved OpenSpec proposal
- **THEN** developer must follow the 11-step workflow:
  1. Read proposal.md
  2. Read design.md if exists
  3. Read tasks.md
  4. Create database migrations for schema changes
  5. Write tests before implementation
  6. Implement tasks sequentially with frequent commits
  7. Update documentation
  8. Run all quality checks
  9. Create pull request with spec reference
  10. Update task checklist
  11. Wait for approval before starting

#### Scenario: Skipping workflow steps
- **WHEN** a developer tries to skip workflow steps
- **THEN** the PR review must catch the omission
- **AND** the PR cannot be merged until all steps complete
- **AND** automated checks must enforce quality gates