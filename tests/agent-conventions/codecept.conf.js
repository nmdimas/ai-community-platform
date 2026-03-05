/** @type {CodeceptJS.MainConfig} */
exports.config = {
    tests: './tests/**/*_test.js',
    output: './output',
    helpers: {
        REST: {
            endpoint: process.env.AGENT_URL || 'http://localhost:80',
            defaultHeaders: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            timeout: 10000,
        },
        JSONResponse: {},
    },
    plugins: {
        retryFailedStep: {
            enabled: false,
        },
    },
    name: 'agent-conventions',
};
