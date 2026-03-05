// Smoke: core platform health endpoint
// Migrated from tests/health.spec.ts

Feature('Smoke: Health Endpoint');

Scenario('returns 200 with ok status through Traefik', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    I.assertEqual(res.status, 200);
    I.assertEqual(res.data.status, 'ok');
    I.assertEqual(res.data.service, 'core-platform');
}).tag('@smoke');

Scenario('is accessible without authentication', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    I.assertEqual(res.status, 200);
}).tag('@smoke');

Scenario('returns application/json content-type', async ({ I }) => {
    const res = await I.sendGetRequest('/health');
    I.assertContain(res.headers['content-type'], 'application/json');
}).tag('@smoke');
