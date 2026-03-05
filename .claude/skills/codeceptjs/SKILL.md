---
name: codeceptjs
description: Use when writing or reviewing CodeceptJS tests (Codeception for JS). Covers best practices, page objects, locators, configuration, and data patterns.
---

# CodeceptJS Best Practices

Use when writing, reviewing, or debugging CodeceptJS tests. Follows official best practices from codecept.io.

## Readability First

Tests should read like documentation. Use semantic actions, not raw selectors:

```javascript
// Bad — brittle CSS selector
I.click({css: 'nav.user .user-login'});

// Good — readable, uses visible text
I.click('Login', 'nav.user');

// Complex — use locator builder
I.click(locate('.button').withText('Click me'));
```

Rule: if your code goes beyond using the `I` object or page objects, you are probably doing something wrong.

## Test Structure

Keep tests short and focused. Create data via API, not UI:

```javascript
Scenario('editing a metric', async ({ I, loginAs, metricPage }) => {
  loginAs('admin');
  const metric = await I.have('metric', { type: 'memory', duration: 'day' });
  metricPage.open(metric.id);
  I.click('Edit');
  I.see('Editing Metric');
  I.selectFromDropdown('duration', 'week');
  I.click('Save');
  I.see('Duration: Week', '.summary');
});
```

Key shortcuts:
- Create test data via API, not UI steps
- Use `autoLogin` plugin instead of embedding login logic
- Break lengthy tests into focused scenarios
- Leverage custom steps and page objects

## Locator Strategy

1. **Text-based** — best for readability on stable, single-language sites
2. **Strict locators** (`{ css }` or `{ xpath }`) — when predictability matters
3. **`data-test` / `data-qa` attributes** — with `customLocator` plugin for resilience

## Page Objects Architecture

| Layer | Purpose | Example |
|-------|---------|---------|
| **Actor** (`custom_steps.js`) | Site-wide actions | login, global controls |
| **Page Objects** | Page-specific actions & selectors | one per screen in SPAs |
| **Page Fragments** | Reusable widgets | navigation, modals |
| **Helpers** | Low-level driver access | DB, email, filesystem |

### Page Object Pattern

```javascript
class CheckoutForm {
  fillBillingInformation(data = {}) {
    for (let key of Object.keys(data)) {
      I.fillField(key, data[key]);
    }
  }
}
module.exports = new CheckoutForm();
module.exports.CheckoutForm = CheckoutForm;
```

### Component Objects (reusable widgets)

```javascript
const { I } = inject();

class DatePicker {
  selectToday(locator) {
    I.click(locator);
    I.click('.currentDate', '.date-picker');
  }
  selectInNextMonth(locator, date = '15') {
    I.click(locator);
    I.click('show next month', '.date-picker');
    I.click(date, '.date-picker');
  }
}
module.exports = new DatePicker();
module.exports.DatePicker = DatePicker;
```

Guidelines:
- Use classes for inheritance capability
- Export both class and instance
- Only create page objects for shared, reused pages
- Accept flexible argument objects for forms

## Configuration

Environment-specific configs:

```javascript
// codecept.conf.js, codecept.ci.conf.js, codecept.windows.conf.js
require('dotenv').config({ path: '.env' });
```

Modularize with `config/` directory:

```javascript
// config/components.js
module.exports = {
  DatePicker: './components/datePicker',
  Dropdown: './components/dropdown',
};

// Main config
include: {
  I: './steps_file',
  ...require('./config/pages'),
  ...require('./config/components'),
},
```

Pass config data to tests:

```javascript
bootstrap: () => {
  codeceptjs.container.append({
    testUser: { email: '[email protected]', password: '123456' }
  });
}
```

## Data Access Objects (DAO)

API/data interaction layer — extends ApiDataFactory:

```javascript
const { faker } = require('@faker-js/faker');
const { I } = inject();
const { output } = require('codeceptjs');

class InterfaceData {
  async getLanguages() {
    const { data } = await I.sendGetRequest('/api/languages');
    output.debug(`Languages ${data.records.map(r => r.language)}`);
    return data.records;
  }

  async getUsername() {
    return faker.user.name();
  }
}
module.exports = new InterfaceData();
```

DAOs require REST or GraphQL helpers enabled.
