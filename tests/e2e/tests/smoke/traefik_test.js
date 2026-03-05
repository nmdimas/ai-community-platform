// Smoke: Traefik routing
// Migrated from tests/traefik.spec.ts

const TRAEFIK_API = process.env.TRAEFIK_API || 'http://localhost:8080';

Feature('Smoke: Traefik');

Scenario('API is reachable', async ({ I }) => {
    const res = await I.sendGetRequest(`${TRAEFIK_API}/api/http/services`);
    I.assertEqual(res.status, 200);
    I.assertTrue(Array.isArray(res.data), 'Expected array of services');
}).tag('@smoke');

Scenario('core@docker is registered as enabled service', async ({ I }) => {
    const res = await I.sendGetRequest(`${TRAEFIK_API}/api/http/services`);
    const core = res.data.find((s) => s.name === 'core@docker');
    I.assertTruthy(core, 'core@docker service must exist');
    I.assertEqual(core.status, 'enabled');
}).tag('@smoke');

Scenario('knowledge-agent@docker is registered', async ({ I }) => {
    const res = await I.sendGetRequest(`${TRAEFIK_API}/api/http/services`);
    const agent = res.data.find((s) => s.name === 'knowledge-agent@docker');
    I.assertTruthy(agent, 'knowledge-agent@docker must be registered in Traefik');
    I.assertEqual(agent.status, 'enabled');
}).tag('@smoke');

Scenario('news-maker-agent@docker is registered', async ({ I }) => {
    const res = await I.sendGetRequest(`${TRAEFIK_API}/api/http/services`);
    const agent = res.data.find((s) => s.name === 'news-maker-agent@docker');
    I.assertTruthy(agent, 'news-maker-agent@docker must be registered in Traefik');
    I.assertEqual(agent.status, 'enabled');
}).tag('@smoke');

Scenario('hello-agent@docker is registered', async ({ I }) => {
    const res = await I.sendGetRequest(`${TRAEFIK_API}/api/http/services`);
    const agent = res.data.find((s) => s.name === 'hello-agent@docker');
    I.assertTruthy(agent, 'hello-agent@docker must be registered in Traefik');
    I.assertEqual(agent.status, 'enabled');
}).tag('@smoke');
