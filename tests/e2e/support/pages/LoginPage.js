const { I } = inject();

module.exports = {
    url: '/admin/login',

    fields: {
        username: '_username',
        password: '_password',
    },

    submitButton: 'button[type="submit"]',

    async login(username, password) {
        I.amOnPage(this.url);
        I.fillField(this.fields.username, username);
        I.fillField(this.fields.password, password);
        I.click(this.submitButton);
        await I.waitForElement('.sidebar-nav', 5);
    },

    async loginAsAdmin() {
        await this.login('admin', process.env.ADMIN_PASSWORD || 'test-password');
    },
};
