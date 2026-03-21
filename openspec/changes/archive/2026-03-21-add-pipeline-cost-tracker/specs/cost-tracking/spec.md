## ADDED Requirements

### Requirement: Cost Tracker Module
The pipeline SHALL include a `builder/cost-tracker.sh` module that calculates approximate cost of AI provider usage based on token counts from `.meta.json` files and known pricing per model.

#### Scenario: Calculate cost for a completed agent step
- **GIVEN** a `.meta.json` file with `tokens.input_tokens`, `tokens.output_tokens`, `tokens.cache_read` and `model` field
- **WHEN** `calculate_step_cost` is called with the meta file path
- **THEN** it returns the estimated cost in USD based on the model's pricing tier
- **AND** the formula is: `(input × input_price + output × output_price + cache_read × cache_price) / 1_000_000`

#### Scenario: Detect provider and model tier from model string
- **GIVEN** a model string like `anthropic/claude-opus-4-20250514`
- **WHEN** `detect_pricing_tier` is called
- **THEN** it returns the correct pricing tuple (input/output/cache per 1M tokens)

#### Scenario: Unknown model falls back to zero cost
- **GIVEN** a model string that doesn't match any known pricing
- **WHEN** `detect_pricing_tier` is called
- **THEN** it returns `0:0:0` (no charge assumed)

---

### Requirement: Subscription Plan Configuration
The pipeline SHALL read subscription plan type from ENV variables and calculate daily budget accordingly.

#### Scenario: Configure Anthropic subscription plan
- **GIVEN** `PIPELINE_PLAN_ANTHROPIC=max5x` in `.env.local`
- **WHEN** the cost tracker initializes
- **THEN** it sets the monthly budget to $100 and daily budget to ~$3.33

#### Scenario: Configure OpenAI subscription plan
- **GIVEN** `PIPELINE_PLAN_OPENAI=plus` in `.env.local`
- **WHEN** the cost tracker initializes
- **THEN** it sets the monthly budget to $20 and daily budget to ~$0.67

#### Scenario: API-only mode (no subscription limit)
- **GIVEN** `PIPELINE_PLAN_ANTHROPIC=api` in `.env.local`
- **WHEN** the cost tracker calculates usage percentage
- **THEN** it shows cost but no percentage (unlimited pay-per-token)

---

### Requirement: Daily Usage Aggregation
The pipeline SHALL aggregate costs from all `.meta.json` files created today and compare against daily budget.

#### Scenario: Aggregate today's usage across multiple tasks
- **GIVEN** multiple `.meta.json` files from today's pipeline runs
- **WHEN** `aggregate_daily_usage` is called
- **THEN** it sums costs per provider and returns total per provider with percentage of daily budget

#### Scenario: Color-coded usage thresholds
- **GIVEN** daily usage percentage for a provider
- **WHEN** the monitor displays the cost bar
- **THEN** 0-70% is shown in green, 70-90% in yellow, 90%+ in red

---

### Requirement: Pipeline Integration
The pipeline SHALL emit cost events after each agent completes and display aggregated costs in the monitor Activity tab.

#### Scenario: Emit cost event after agent completion
- **WHEN** an agent completes and its `.meta.json` is written
- **THEN** pipeline.sh emits a `COST` event to `events.log` with agent, provider, model, step cost, and daily usage percentage

#### Scenario: Display cost summary in Activity tab footer
- **WHEN** the monitor renders the Activity tab
- **THEN** it shows a footer line with per-provider daily spend and percentage (e.g., `Anthropic: $1.23/~$3.33 (37%)`)

---

### Requirement: ENV Configuration Template
The `.env.local.example` SHALL document all available plan options with pricing comments.

#### Scenario: Plan options documented in .env.local.example
- **GIVEN** a new developer reads `.env.local.example`
- **THEN** they see commented sections for each provider with available plans and their monthly prices
- **AND** default values are set to the most common free/cheap options
