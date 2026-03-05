// Custom steps shared across all tests.
// Add reusable helpers here (e.g. loginAsAdmin).

module.exports = function () {
    return actor({
        /**
         * Login as admin and land on dashboard.
         */
        loginAsAdmin: async function () {
            await this.amOnPage('/admin/login');
            await this.fillField('_username', 'admin');
            await this.fillField('_password', process.env.ADMIN_PASSWORD || 'test-password');
            await this.click('button[type="submit"]');
            await this.waitForElement('.sidebar-nav', 5);
        },

        /**
         * Ensure JWT edge cookie exists for protected Traefik tools pages.
         */
        ensureEdgeAccess: async function (targetUrl) {
            const cookieName = process.env.EDGE_AUTH_COOKIE_NAME || 'ACP_EDGE_TOKEN';
            await this.amOnPage(`/edge/auth/login?rd=${encodeURIComponent(targetUrl)}`);

            const currentUrl = await this.grabCurrentUrl();
            if (currentUrl.includes('/edge/auth/login')) {
                const hasForm = await this.grabNumberOfVisibleElements('form[action*="/edge/auth/login"]');
                if (hasForm > 0) {
                    await this.fillField('_username', 'admin');
                    await this.fillField('_password', process.env.ADMIN_PASSWORD || 'test-password');
                    await this.click('button[type="submit"]');
                }
            }

            await this.wait(1);
            const cookie = await this.grabCookie(cookieName);
            if (!cookie) {
                throw new Error(`Expected ${cookieName} cookie to be set`);
            }
        },

        /**
         * Drop browser cookies to emulate anonymous user.
         */
        resetBrowserSession: async function () {
            await this.usePlaywrightTo('clear cookies', async ({ browserContext }) => {
                await browserContext.clearCookies();
            });
        },
    });
};
