const assert = require('assert');
const { execSync } = require('child_process');

Feature('Admin: Tools Access');

const OPENCLAW_URL = process.env.OPENCLAW_URL || 'http://openclaw.localhost';
const LANGFUSE_URL = process.env.LANGFUSE_URL || 'http://langfuse.localhost';
const LITELLM_URL = process.env.LITELLM_URL || 'http://litellm.localhost';

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
    async () => {
        // Use curl to test redirect — avoids Chromium DNS cache issues with *.localhost
        const protectedUrls = [
            OPENCLAW_URL + '/',
            LANGFUSE_URL + '/',
            LITELLM_URL + '/',
        ];

        for (const targetUrl of protectedUrls) {
            const result = execSync(
                `curl -s -o /dev/null -w "%{http_code} %{redirect_url}" --max-time 5 "${targetUrl}"`,
                { encoding: 'utf-8' },
            ).trim();

            const [statusCode, redirectUrl] = result.split(' ', 2);
            assert.strictEqual(statusCode, '302', `Expected 302 redirect for ${targetUrl}, got ${statusCode}`);
            assert.ok(
                redirectUrl.includes('/edge/auth/login'),
                `Expected redirect to /edge/auth/login for ${targetUrl}, got: ${redirectUrl}`,
            );
        }
    },
).tag('@admin').tag('@tools').tag('@security');

Scenario(
    'edge login sets JWT cookie and redirects back to tool',
    async () => {
        // Full edge-auth flow via curl: get redirect → login → verify cookie
        const password = process.env.ADMIN_PASSWORD || 'test-password';
        const cookieName = process.env.EDGE_AUTH_COOKIE_NAME || 'ACP_EDGE_TOKEN';

        // Step 1: Hit langfuse — should get 302 to edge login
        const redirectResult = execSync(
            `curl -s -o /dev/null -w "%{redirect_url}" --max-time 5 "${LANGFUSE_URL}/"`,
            { encoding: 'utf-8' },
        ).trim();
        assert.ok(redirectResult.includes('/edge/auth/login'), `Expected edge login redirect, got: ${redirectResult}`);

        // Step 2: Submit login form via curl, capture JWT cookie
        const cookieResult = execSync(
            `curl -s -c - -L --max-time 10 -d '_username=admin&_password=${password}' '${redirectResult}'`,
            { encoding: 'utf-8' },
        );
        assert.ok(cookieResult.includes(cookieName), `Expected ${cookieName} cookie after login`);

        // Step 3: Use cookie to access langfuse — should get 200 (not 302)
        const jwtLine = cookieResult.split('\n').find(l => l.includes(cookieName));
        if (jwtLine) {
            const jwtValue = jwtLine.split('\t').pop().trim();
            const statusCode = execSync(
                `curl -s -o /dev/null -w "%{http_code}" --max-time 5 -b "${cookieName}=${jwtValue}" "${LANGFUSE_URL}/"`,
                { encoding: 'utf-8' },
            ).trim();
            assert.notStrictEqual(statusCode, '302', 'Authenticated request should not redirect');
        }
    },
).tag('@admin').tag('@tools').tag('@security');

Scenario(
    'edge login on openclaw subdomain renders OpenClaw UI after auth',
    async () => {
        const password = process.env.ADMIN_PASSWORD || 'test-password';
        const cookieName = process.env.EDGE_AUTH_COOKIE_NAME || 'ACP_EDGE_TOKEN';

        // Step 1: Hit openclaw — should 302 to edge login on same subdomain
        const redirectResult = execSync(
            `curl -s -o /dev/null -w "%{redirect_url}" --max-time 5 "${OPENCLAW_URL}/"`,
            { encoding: 'utf-8' },
        ).trim();
        assert.ok(
            redirectResult.includes('openclaw.localhost/edge/auth/login'),
            `Expected redirect to openclaw.localhost/edge/auth/login, got: ${redirectResult}`,
        );
        assert.ok(
            redirectResult.includes('rd='),
            `Expected rd= param in redirect URL, got: ${redirectResult}`,
        );

        // Step 2: Login via subdomain login page — follow redirect, capture response
        const loginResult = execSync(
            `curl -s -c - -L --max-time 10 -d '_username=admin&_password=${password}' '${redirectResult}'`,
            { encoding: 'utf-8' },
        );
        assert.ok(loginResult.includes(cookieName), `Expected ${cookieName} cookie after login`);
        assert.ok(
            loginResult.includes('<openclaw-app>') || loginResult.includes('OpenClaw'),
            'Expected OpenClaw UI to render after login',
        );

        // Step 3: Use cookie to access openclaw — should get 200
        const jwtLine = loginResult.split('\n').find(l => l.includes(cookieName));
        if (jwtLine) {
            const jwtValue = jwtLine.split('\t').pop().trim();
            const statusCode = execSync(
                `curl -s -o /dev/null -w "%{http_code}" --max-time 5 -b "${cookieName}=${jwtValue}" "${OPENCLAW_URL}/"`,
                { encoding: 'utf-8' },
            ).trim();
            assert.strictEqual(statusCode, '200', `Expected 200 after auth, got ${statusCode}`);
        }
    },
).tag('@admin').tag('@tools').tag('@security');

Scenario(
    'openclaw messenger endpoint is accessible without edge-login redirect',
    async () => {
        const result = execSync(
            `curl -s -o /dev/null -w "%{http_code}" --max-time 5 "${OPENCLAW_URL}/api/channels/telegram/webhook"`,
            { encoding: 'utf-8' },
        ).trim();

        // Should NOT be 302 redirect to edge login
        assert.notStrictEqual(result, '302', 'Webhook endpoint should not redirect to edge login');
    },
).tag('@admin').tag('@tools').tag('@security');
