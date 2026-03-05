<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

final class AgentConfigCest
{
    private function login(\FunctionalTester $I): void
    {
        $I->sendPost('/admin/login', [
            '_username' => 'admin',
            '_password' => 'test-password',
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function agentsPageContainsSettingsLink(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendGet('/admin/agents');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('/settings');
        $I->seeResponseContains('Налаштування');
    }

    public function agentSettingsPageContainsConfigForm(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendGet('/admin/agents/knowledge-agent/settings');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('configDescription');
        $I->seeResponseContains('configSystemPrompt');
        $I->seeResponseContains('Конфігурація');
    }

    public function configUpdateEndpointRequiresAuth(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/api/v1/internal/agents/test-agent/config', json_encode([
            'description' => 'Test',
            'system_prompt' => 'Test prompt',
        ], JSON_THROW_ON_ERROR));
        $I->seeResponseContains('_username');
    }
}
