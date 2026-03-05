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
    });
};
