import { test, expect } from '@playwright/test';

const TRAEFIK_API = 'http://localhost:8080';

type TraefikService = {
    name: string;
    provider: string;
    status: string;
    type: string;
};

type TraefikRouter = {
    name: string;
    provider: string;
    status: string;
    rule: string;
    entryPoints: string[];
    service: string;
};

test.describe('Traefik', () => {
    test('API is reachable', async ({ request }) => {
        const response = await request.get(`${TRAEFIK_API}/api/http/services`);

        expect(response.status()).toBe(200);

        const body = await response.json() as TraefikService[];
        expect(Array.isArray(body)).toBe(true);
    });

    test('core@docker is registered as a service', async ({ request }) => {
        const response = await request.get(`${TRAEFIK_API}/api/http/services`);
        const services = await response.json() as TraefikService[];

        const coreService = services.find((s) => s.name === 'core@docker');
        expect(coreService).toBeDefined();
        expect(coreService?.status).toBe('enabled');
    });

    test('core router is active', async ({ request }) => {
        const response = await request.get(`${TRAEFIK_API}/api/http/routers`);
        const routers = await response.json() as TraefikRouter[];

        const coreRouter = routers.find((r) => r.name === 'core@docker');
        expect(coreRouter).toBeDefined();
        expect(coreRouter?.status).toBe('enabled');
        expect(coreRouter?.entryPoints).toContain('web');
    });
});
