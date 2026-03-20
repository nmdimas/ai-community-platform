# Design: add-pipeline-cost-tracker

## Architecture

```
.env.local (plan config)
    ↓
builder/cost-tracker.sh (pricing module)
    ↓ reads
.opencode/pipeline/logs/*_plan.json (provider per agent)
.opencode/pipeline/logs/*.meta.json (token usage)
    ↓ calculates
cost per agent, cost per provider, % of plan limit
    ↓ outputs
events.log → monitor Activity tab
task metadata → done/*.md footer
```

## Pricing Data Model

```bash
# Provider pricing (per 1M tokens, USD)
# Format: INPUT_PRICE:OUTPUT_PRICE:CACHE_READ_PRICE

ANTHROPIC_OPUS_PRICING="5.00:25.00:0.50"
ANTHROPIC_SONNET_PRICING="3.00:15.00:0.30"
ANTHROPIC_HAIKU_PRICING="1.00:5.00:0.10"
OPENAI_GPT5_PRICING="1.25:10.00:0.125"
OPENAI_CODEX_PRICING="1.50:6.00:0.15"
OPENAI_GPT54_PRICING="1.75:14.00:0.175"
GOOGLE_GEMINI_PRO_PRICING="2.00:12.00:0.20"
GOOGLE_GEMINI_FLASH_PRICING="0.30:2.50:0.03"
OPENROUTER_MARKUP="1.055"  # 5.5% markup on provider rates
```

## Subscription Plan Model

```bash
# Plan type determines monthly budget
# Anthropic: pro=$20, max5x=$100, max20x=$200, api=unlimited(pay-per-token)
# OpenAI: plus=$20, pro=$200, api=unlimited(pay-per-token)
# Google: free=$0, pro=$20, ultra=$42

PIPELINE_PLAN_ANTHROPIC="max5x"     # $100/mo ≈ ~$3.33/day
PIPELINE_PLAN_OPENAI="plus"         # $20/mo ≈ ~$0.67/day
PIPELINE_PLAN_GOOGLE="free"         # rate-limited only
PIPELINE_PLAN_OPENROUTER_BUDGET="100"  # $100 prepaid balance
```

## Cost Calculation

```
cost_per_step = (input_tokens × input_price + output_tokens × output_price + cache_read × cache_price) / 1_000_000
```

Model detection from `meta.json.model` field:
- `anthropic/claude-opus-*` → ANTHROPIC_OPUS_PRICING
- `anthropic/claude-sonnet-*` → ANTHROPIC_SONNET_PRICING
- `openai/gpt-5.4` → OPENAI_GPT54_PRICING
- `openai/codex-*` → OPENAI_CODEX_PRICING
- `google/gemini-*-pro*` → GOOGLE_GEMINI_PRO_PRICING
- `google/gemini-*-flash*` → GOOGLE_GEMINI_FLASH_PRICING

## Budget Tracking

For subscription plans, estimate daily budget:
```
daily_budget = monthly_price / 30
used_today = sum(cost for all meta.json from today)
usage_pct = (used_today / daily_budget) × 100
```

Thresholds:
- 0-70% → green
- 70-90% → yellow
- 90%+ → red (warning in monitor)

## Monitor Integration

New event type `COST` emitted after each agent completes:
```
EPOCH|TIME|COST|agent=coder|provider=anthropic|model=sonnet-4|cost=$0.42|daily=35%
```

Activity tab footer line:
```
  Anthropic: $1.23/~$3.33 (37%)  OpenAI: $0.15/~$0.67 (22%)
```

## File Structure

```
builder/
├── cost-tracker.sh           # Pricing module (sourced by pipeline.sh)
├── pipeline.sh               # Calls cost-tracker after each agent
└── monitor/
    └── pipeline-monitor.sh   # Renders cost in Activity tab

.env.local.example            # Plan config with comments
```
