# E2E Testing Guide

## Stack

| Tool | Purpose |
|------|---------|
| **Codecept.js** | Test runner + scenario DSL |
| **Playwright** | Browser engine (Chromium) |
| **REST helper** | API requests (no browser) |

All E2E tests live in `tests/e2e/`. They test the **full running stack** (requires `make up`).

---

## Directory Structure

```
tests/e2e/
├── codecept.conf.js          # Runner config, helpers, page objects
├── package.json
├── support/
│   ├── steps_file.js         # Shared custom steps (e.g. loginAsAdmin)
│   └── pages/
│       ├── LoginPage.js      # /admin/login interactions
│       └── AgentsPage.js     # /admin/agents interactions
└── tests/
    ├── smoke/                # Fast API checks, no browser
    │   ├── health_test.js    # Core /health endpoint
    │   └── traefik_test.js   # Traefik routing + registered services
    └── admin/
        └── agents_test.js    # Agents page UI + discovery + healthy badges
```

---

## Running Tests

```bash
# Full E2E suite (stack must be up)
make e2e

# Specific tag only
cd tests/e2e && npx codeceptjs run --steps --grep @smoke
cd tests/e2e && npx codeceptjs run --steps --grep @admin

# Headed (see browser)
HEADLESS=false make e2e

# Custom base URL
BASE_URL=http://staging.example.com make e2e
```

---

## Writing a New Test

### 1. Choose the right folder

| Scenario type | Folder | Helper used |
|--------------|--------|-------------|
| API endpoint check (no UI) | `tests/smoke/` | REST |
| Admin panel UI interaction | `tests/admin/` | Playwright |
| Cross-service user journey | `tests/admin/` or new folder | Playwright |

### 2. File naming

Files must end in `_test.js`. Example: `knowledge_upload_test.js`.

### 3. Scenario anatomy

```js
Feature('Admin: My Feature');

// Before hook — runs before EACH scenario in this file
Before(async ({ I, loginPage }) => {
    await loginPage.loginAsAdmin();
});

Scenario('does something useful', async ({ I, agentsPage }) => {
    await agentsPage.open();
    I.see('Expected text');
    I.seeElement('.some-class');
}).tag('@admin');
```

### 4. API-only test (smoke)

```js
Feature('Smoke: My Endpoint');

Scenario('returns 200', async ({ I }) => {
    const res = await I.sendGetRequest('/api/v1/some-endpoint');
    I.assertEqual(res.status, 200);
    I.assertEqual(res.data.key, 'expected-value');
}).tag('@smoke');
```

### 5. Adding a Page Object

Create `support/pages/MyPage.js`:

```js
const { I } = inject();

module.exports = {
    url: '/admin/my-page',

    async open() {
        I.amOnPage(this.url);
        await I.waitForElement('table', 10);
    },

    seeItem(name) {
        I.see(name, 'table');
    },
};
```

Register it in `codecept.conf.js`:

```js
include: {
    myPage: './support/pages/MyPage.js',
},
```

Use in tests:

```js
Scenario('example', async ({ I, myPage }) => {
    await myPage.open();
    myPage.seeItem('expected-item');
});
```

---

## Tags

| Tag | Meaning |
|-----|---------|
| `@smoke` | Fast API checks, no browser, runs in CI |
| `@admin` | Admin panel UI tests |
| `@knowledge` | Knowledge agent flows |
| `@news` | News maker agent flows |

---

## What "All Agents Healthy" Means

The `tests/admin/agents_test.js` suite asserts the following after a discovery run:

1. `knowledge-agent` row is present in the table
2. `news-maker-agent` row is present in the table
3. Both have a `.badge-healthy` element (green badge)
4. Zero `.badge-error`, `.badge-degraded`, `.badge-unavailable` badges exist

For this to pass, each agent **must**:
- Be discoverable via Traefik (`*-agent@docker` service)
- Respond to `GET /api/v1/manifest` with valid JSON (name + version)
- Return HTTP 200 on `GET /health`

See [agent-requirements/conventions.md](agent-requirements/conventions.md) for full spec.
See [agent-requirements/agent-state-model.md](agent-state-model.md) for badge semantics and operator hint contract.

---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `BASE_URL` | `http://localhost` | Platform base URL |
| `TRAEFIK_API` | `http://localhost:8080` | Traefik dashboard API |
| `ADMIN_PASSWORD` | `test-password` | Admin login password |
| `HEADLESS` | `true` | Set to `false` to see browser |
