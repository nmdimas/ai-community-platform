// E2E: Knowledge Agent Admin Panel
// Tests settings, CRUD, and encyclopedia toggle via the admin panel.

Feature('Admin: Knowledge Agent Admin Panel');

Before(async ({ I, loginPage }) => {
    await loginPage.loginAsAdmin();

    // Accept all confirm() dialogs automatically
    I.usePlaywrightTo('auto-accept dialogs', async ({ page }) => {
        page.on('dialog', async (dialog) => {
            await dialog.accept();
        });
    });
});

Scenario(
    'knowledge-agent settings page loads in iframe',
    async ({ I }) => {
        I.amOnPage('/admin/agents/knowledge-agent/settings');
        await I.waitForElement('iframe', 10);
        I.seeElement('iframe');

        // Switch into iframe context
        await I.switchTo('iframe');
        await I.waitForElement('.tabs', 10);
        I.see('Записи');
        I.see('Налаштування');
        I.see('DLQ Монітор');
        I.see('Перевірка');
        await I.switchTo();
    },
).tag('@admin').tag('@knowledge');

Scenario(
    'can toggle encyclopedia visibility in settings',
    async ({ I }) => {
        I.amOnPage('/admin/agents/knowledge-agent/settings');
        await I.waitForElement('iframe', 10);
        await I.switchTo('iframe');
        await I.waitForElement('.tabs', 10);

        // Navigate to Settings tab
        I.click('Налаштування');
        await I.waitForElement('.toggle-wrap', 5);

        // Disable encyclopedia by clicking the toggle label
        I.click('.toggle');
        I.click('Зберегти');
        await I.waitForText('Збережено', 5);

        // Switch out and verify wiki returns 503 (wiki is on knowledge-agent port 8083)
        await I.switchTo();
        await I.ensureEdgeAccess('http://localhost:8083/wiki');
        I.amOnPage('http://localhost:8083/wiki');
        I.see('недоступна');

        // Re-enable encyclopedia
        I.amOnPage('/admin/agents/knowledge-agent/settings');
        await I.waitForElement('iframe', 10);
        await I.switchTo('iframe');
        await I.waitForElement('.tabs', 10);
        I.click('Налаштування');
        await I.waitForElement('.toggle-wrap', 5);
        I.click('.toggle');
        I.click('Зберегти');
        await I.waitForText('Збережено', 5);
        await I.switchTo();
    },
).tag('@admin').tag('@knowledge');

Scenario(
    'can save base instructions',
    async ({ I }) => {
        I.amOnPage('/admin/agents/knowledge-agent/settings');
        await I.waitForElement('iframe', 10);
        await I.switchTo('iframe');
        await I.waitForElement('.tabs', 10);

        // Navigate to Settings tab
        I.click('Налаштування');
        await I.waitForElement('#baseInstructions', 5);

        // Fill in and save base instructions
        I.fillField('#baseInstructions', 'E2E test instructions for knowledge extraction');
        I.click('Зберегти');
        await I.waitForText('Збережено', 5);

        // Security instructions are visible and disabled
        I.seeElement('textarea[disabled]');
        I.see('Інструкції безпеки');
        await I.switchTo();
    },
).tag('@admin').tag('@knowledge');
