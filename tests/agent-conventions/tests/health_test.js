// TC-02: Agent Health Endpoint
// Validates that /health returns a compliant response.

Feature('TC-02 Agent Health Endpoint');

Scenario('GET /health returns HTTP 200', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    I.assertEqual(res.status, 200, `Expected 200, got ${res.status}`);
});

Scenario('health endpoint returns JSON', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    I.assertContain(res.headers['content-type'], 'application/json');
});

Scenario('health response has status field', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    I.assertEqual(res.status, 200);
    const body = res.data;
    I.assertTruthy(body.status !== undefined, 'health response must have a "status" field');
});

Scenario('health status is ok when service is running', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    const { status } = res.data;
    I.assertEqual(status, 'ok', `health status must be "ok", got "${status}"`);
});
