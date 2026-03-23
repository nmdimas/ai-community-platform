## ADDED Requirements

### Requirement: Translater Pipeline Agent

The pipeline SHALL include a `translater` agent that translates content between supported languages (Ukrainian and English) with context awareness and term consistency.

The translater agent SHALL be available in both workflows:
- Ultraworks: `s-translater` subagent delegated by Sisyphus
- Builder: `translater` primary agent

The translater agent SHALL have edit permissions to directly modify translation files, templates, and documentation.

The translater agent SHALL NOT mechanically translate:
- Code identifiers (variable names, class names, function names)
- Technical terms that are conventionally kept in English (e.g., "Docker", "Redis", "API", "A2A")
- Brand names and product names (e.g., "OpenClaw", "Langfuse", "LiteLLM")
- Configuration keys and YAML keys

#### Scenario: Translate new UI labels after feature implementation
- **WHEN** coder adds new keys to `messages.en.yaml`
- **THEN** translater adds corresponding keys to `messages.uk.yaml` with contextually appropriate Ukrainian translations
- **AND** translater checks existing translations for term consistency

#### Scenario: Mirror documentation after documenter creates new docs
- **WHEN** documenter creates `docs/features/ua/new-feature.md`
- **AND** the EN mirror does not exist or is outdated
- **THEN** translater creates or updates `docs/features/en/new-feature.md` with matching structure and headings

#### Scenario: Detect missing translations
- **WHEN** translater scans YAML message files
- **AND** a key exists in one language but not the other
- **THEN** translater adds the missing translation and reports it in handoff

### Requirement: Security-Review Pipeline Agent

The pipeline SHALL include a `security-review` agent that performs deep security analysis of PHP/Symfony code with OWASP ASVS 5.0 category mapping and severity ratings.

The security-review agent SHALL be available in both workflows:
- Ultraworks: `s-security-review` subagent delegated by Sisyphus
- Builder: `security-review` primary agent

The security-review agent SHALL be read-only and SHALL NOT modify source code.

The security-review agent SHALL produce a structured report with:
- Finding ID, severity (CRITICAL/HIGH/MEDIUM/LOW/INFO), OWASP ASVS category
- File path and line number
- Description of the vulnerability
- Recommended remediation

#### Scenario: Review authentication code changes
- **WHEN** coder modifies files in auth/security namespaces
- **THEN** security-review checks for broken authentication, missing authorization, session fixation, CSRF bypass, and insecure token handling
- **AND** maps findings to OWASP ASVS V2 (Authentication) and V4 (Access Control)

#### Scenario: Review input handling changes
- **WHEN** coder modifies controller or form handling code
- **THEN** security-review checks for SQL injection, XSS, command injection, path traversal, SSRF, and open redirects
- **AND** maps findings to OWASP ASVS V5 (Validation) and V14 (Configuration)

#### Scenario: Security-review is optional and advisory
- **WHEN** Sisyphus evaluates whether to run security-review
- **AND** the change does not touch security-sensitive code paths
- **THEN** security-review phase is skipped
- **WHEN** security-review runs and finds issues
- **THEN** findings are reported as advisory (WARN level in pipeline) unless CRITICAL severity

### Requirement: Translater Model Routing

The translater agent SHALL use `google/gemini-3.1-pro-preview` as primary model for strong multilingual and writing capability.

The translater agent SHALL have a fallback chain covering at least 5 alternative providers.

#### Scenario: Model fallback on rate limit
- **WHEN** primary model `google/gemini-3.1-pro-preview` is rate-limited
- **THEN** the system falls back to `openai/gpt-5.4`, then `anthropic/claude-sonnet-4-6`, then remaining providers in order

### Requirement: Security-Review Model Routing

The security-review agent SHALL use `anthropic/claude-opus-4-6` as primary model for strongest analytical reasoning.

The security-review agent SHALL have a fallback chain covering at least 5 alternative providers.

#### Scenario: Model fallback on rate limit
- **WHEN** primary model `anthropic/claude-opus-4-6` is rate-limited
- **THEN** the system falls back to `openai/gpt-5.4`, then `opencode-go/glm-5`, then remaining providers in order

### Requirement: Pipeline Phase Integration

The translater agent SHALL run as Phase 6b (optional, after documenter, before summarizer) in the Ultraworks pipeline.

The security-review agent SHALL run as Phase 5b (optional, after auditor loop, before documenter) in the Ultraworks pipeline.

#### Scenario: Translater triggered by i18n changes
- **WHEN** a pipeline change touches `translations/*.yaml`, `*.html.twig` with trans filter, or `docs/**/*.md`
- **THEN** Sisyphus delegates to `s-translater` after documenter completes

#### Scenario: Security-review triggered by security-sensitive changes
- **WHEN** a pipeline change touches auth controllers, security voters, form types, file upload handlers, or HTTP client code
- **THEN** Sisyphus delegates to `s-security-review` after auditor loop completes

#### Scenario: Both agents skipped when not relevant
- **WHEN** a pipeline change only modifies test files or documentation structure
- **THEN** both translater and security-review phases are skipped
