## ADDED Requirements

### Requirement: Source Registry
The system SHALL maintain a registry of open web sources for AI news crawling, with per-source enablement and crawl priority.

#### Scenario: Enabled sources are eligible for crawling
- **WHEN** a scheduled or manual crawl run starts
- **THEN** the system loads only sources where `enabled = true`

#### Scenario: Disabled source is skipped
- **WHEN** a source is marked disabled in admin
- **THEN** the crawler excludes it from future runs without deleting historical records

### Requirement: Schema-Agnostic Crawling
The system SHALL fetch and parse supported source pages through a universal crawler/parser adapter that does not depend on hardcoded per-site schemas.

#### Scenario: Generic article extraction succeeds
- **WHEN** the crawler processes a supported public article page
- **THEN** it returns normalized candidate content including title, body text, and canonical URL

#### Scenario: Poorly structured page fails gracefully
- **WHEN** the crawler cannot extract a usable article candidate from a page
- **THEN** the system records the crawl failure and continues processing other sources

### Requirement: Raw Temporary Storage
The system SHALL persist parsed source candidates in a temporary raw-news table before editorial processing.

#### Scenario: New candidate stored
- **WHEN** the crawler extracts a non-duplicate article candidate
- **THEN** the system inserts a `raw_news_items` record with `status = new` and an `expires_at` timestamp

#### Scenario: Duplicate candidate skipped
- **WHEN** a new candidate matches an existing deduplication hash
- **THEN** the system does not create a second active raw-news record for the same content

### Requirement: Optional Proxy Usage
The system SHALL support optional proxy configuration for crawling, disabled by default.

#### Scenario: Proxy disabled by default
- **WHEN** the service starts with default settings
- **THEN** crawl requests are sent directly without proxy routing

#### Scenario: Proxy enabled by configuration
- **WHEN** an operator enables the proxy in admin settings
- **THEN** subsequent crawl requests use the configured proxy settings
