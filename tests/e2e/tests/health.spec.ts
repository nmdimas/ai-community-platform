import { test, expect } from '@playwright/test';

test.describe('Health endpoint', () => {
    test('returns 200 with ok status through Traefik', async ({ request }) => {
        const response = await request.get('/health');

        expect(response.status()).toBe(200);

        const body = await response.json() as { status: string; service: string; version: string };
        expect(body.status).toBe('ok');
        expect(body.service).toBe('core-platform');
    });

    test('is accessible without authentication', async ({ request }) => {
        const response = await request.get('/health');

        expect(response.status()).toBe(200);
    });

    test('returns application/json content type', async ({ request }) => {
        const response = await request.get('/health');

        expect(response.headers()['content-type']).toContain('application/json');
    });
});
