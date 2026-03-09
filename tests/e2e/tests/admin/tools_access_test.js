Feature('Admin: Tools Access');

const OPENCLAW_URL = process.env.OPENCLAW_URL || 'http://openclaw.localhost';
const LANGFUSE_URL = process.env.LANGFUSE_URL || 'http://langfuse.localhost';
const LITELLM_URL = process.env.LITELLM_URL || 'http://litellm.localhost';
const TRAEFIK_URL = process.env.TRAEFIK_URL || 'http://traefik.localhost';

Before(async ({ I, loginPage }) => {
    await loginPage.loginAsAdmin();
});

Scenario(
    'dashboard shows tool cards for Langfuse, LiteLLM, Traefik',
    async ({ I }) => {
        I.amOnPage('/admin/dashboard');
        I.see('Langfuse');
        I.see('LiteLLM');
        I.see('Traefik');
    },
).tag('@admin').tag('@tools');

Scenario(
    'anonymous user is redirected to edge login on protected tools',
    async ({ I }) => {
        await I.resetBrowserSession();

        const protectedUrls = [
            OPENCLAW_URL + '/',
            LANGFUSE_URL + '/',
            LITELLM_URL + '/',
        ];

        for (const targetUrl of protectedUrls) {
            I.amOnPage(targetUrl);
            await I.waitForText('Вхід до інструментів', 10);

            const currentUrl = await I.grabCurrentUrl();
            if (!currentUrl.includes('/edge/auth/login')) {
                throw new Error(`Expected edge auth login redirect for ${targetUrl}, got: ${currentUrl}`);
            }
        }
    },
).tag('@admin').tag('@tools').tag('@security');

Scenario(
    'edge login sets JWT cookie and returns user to requested tool URL',
    async ({ I }) => {
        await I.resetBrowserSession();

        I.amOnPage(LANGFUSE_URL + '/');
        await I.waitForText('Вхід до інструментів', 10);

        I.fillField('_username', 'admin');
        I.fillField('_password', process.env.ADMIN_PASSWORD || 'test-password');
        I.click('button[type="submit"]');

        await I.wait(1);
        I.dontSeeInCurrentUrl('/edge/auth/login');
        I.seeInCurrentUrl('langfuse');

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

        I.amOnPage(OPENCLAW_URL + '/api/channels/telegram/webhook');
        await I.wait(1);

        I.dontSeeInCurrentUrl('/edge/auth/login');
        I.seeInCurrentUrl('/api/channels/telegram/webhook');
    },
).tag('@admin').tag('@tools').tag('@security');
