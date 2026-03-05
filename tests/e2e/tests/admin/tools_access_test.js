Feature('Admin: Tools Access');

Before(async ({ I, loginPage }) => {
    await loginPage.loginAsAdmin();
});

Scenario(
    'dashboard has Langfuse button and click opens protected Langfuse route',
    async ({ I }) => {
        I.amOnPage('/admin/dashboard');
        I.see('Відкрити Langfuse');
        I.see('Відкрити LiteLLM');
        I.see('Відкрити Traefik');

        I.click('Відкрити Langfuse');
        await I.wait(1);

        const currentUrl = await I.grabCurrentUrl();
        if (currentUrl.includes('/edge/auth/login')) {
            I.fillField('_username', 'admin');
            I.fillField('_password', process.env.ADMIN_PASSWORD || 'test-password');
            I.click('button[type="submit"]');
            await I.wait(1);
        }

        I.dontSeeInCurrentUrl('/edge/auth/login');
        I.seeInCurrentUrl('localhost:8086');
    },
).tag('@admin').tag('@tools');

Scenario(
    'anonymous user is redirected to edge login on all protected tools entrypoints',
    async ({ I }) => {
        await I.resetBrowserSession();

        const protectedUrls = [
            'http://localhost:8082/',
            'http://localhost:8082/healthz',
            'http://localhost:8083/',
            'http://localhost:8083/wiki',
            'http://localhost:8084/',
            'http://localhost:8084/health',
            'http://localhost:8085/',
            'http://localhost:8085/health',
            'http://localhost:8085/api/v1/manifest',
            'http://localhost:8086/',
            'http://localhost:8086/project',
        ];

        for (const targetUrl of protectedUrls) {
            I.amOnPage(targetUrl);
            await I.waitForText('Вхід до інструментів', 10);

            const currentUrl = await I.grabCurrentUrl();
            if (!currentUrl.includes('/edge/auth/login')) {
                throw new Error(`Expected edge auth login redirect for ${targetUrl}, got: ${currentUrl}`);
            }

            const encodedTarget = encodeURIComponent(targetUrl);
            if (!currentUrl.includes(encodedTarget)) {
                throw new Error(`Expected rd=${encodedTarget} in redirect URL, got: ${currentUrl}`);
            }
        }
    },
).tag('@admin').tag('@tools').tag('@security');

Scenario(
    'edge login sets JWT cookie and returns user to requested tool URL',
    async ({ I }) => {
        await I.resetBrowserSession();

        I.amOnPage('http://localhost:8086/');
        await I.waitForText('Вхід до інструментів', 10);

        I.fillField('_username', 'admin');
        I.fillField('_password', process.env.ADMIN_PASSWORD || 'test-password');
        I.click('button[type="submit"]');

        await I.wait(1);
        I.dontSeeInCurrentUrl('/edge/auth/login');
        I.seeInCurrentUrl('localhost:8086');

        const cookieName = process.env.EDGE_AUTH_COOKIE_NAME || 'ACP_EDGE_TOKEN';
        const cookie = await I.grabCookie(cookieName);
        if (!cookie) {
            throw new Error(`Expected ${cookieName} cookie`);
        }
    },
).tag('@admin').tag('@tools').tag('@security');

Scenario(
    'openclaw messenger endpoint is accessible without edge-login redirect',
    async ({ I }) => {
        await I.resetBrowserSession();

        I.amOnPage('http://localhost:8082/api/channels/telegram/webhook');
        await I.wait(1);

        I.dontSeeInCurrentUrl('/edge/auth/login');
        I.seeInCurrentUrl('/api/channels/telegram/webhook');
    },
).tag('@admin').tag('@tools').tag('@security');
