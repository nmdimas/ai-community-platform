<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

/**
 * Tests the admin interface for agents management.
 *
 * @see docs/specs/admin-requirements.md (Section UI & Navigation)
 * @see docs/specs/api-protocol.md
 */
class AgentsPageCest
{
    private function login(\FunctionalTester $I): void
    {
        $I->sendPost('/admin/login', [
            '_username' => 'admin',
            '_password' => 'test-password',
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function agentsPageRedirectsUnauthenticatedUser(\FunctionalTester $I): void
    {
        $I->sendGet('/admin/agents');
        $I->seeResponseContains('_username');
    }

    public function agentsPageIsAccessibleAfterLogin(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendGet('/admin/agents');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Управління агентами');
        $I->seeResponseContains('Виявити агентів');
    }

    public function agentsPageShowsTableHeaders(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendGet('/admin/agents');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Назва');
        $I->seeResponseContains('Версія');
        $I->seeResponseContains('Здоров');
        $I->seeResponseContains('Статус');
    }

    public function agentsPageShowsEmptyStateOrAgentsList(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendGet('/admin/agents');
        $I->seeResponseCodeIs(200);
        // Page renders table regardless — either empty-state row or agent rows
        $I->seeResponseContains('<table');
        $I->seeResponseContains('<tbody');
    }

    public function discoverEndpointRequiresAuthentication(\FunctionalTester $I): void
    {
        $I->sendPost('/admin/agents/discover');
        // Unauthenticated → redirected to login page
        $I->seeResponseContains('_username');
    }

    public function discoverEndpointReturnsJsonAfterLogin(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
        $I->sendPost('/admin/agents/discover');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        // 'discovered' key must exist with an integer value (actual count depends on running services)
        $I->seeResponseJsonMatchesJsonPath('$.discovered');
        $I->seeResponseJsonMatchesJsonPath('$.results');
    }

    public function agentsPageHasDiscoveryButtonLinkingToCorrectEndpoint(\FunctionalTester $I): void
    {
        $this->login($I);

        $I->sendGet('/admin/agents');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('/admin/agents/discover');
    }
}
