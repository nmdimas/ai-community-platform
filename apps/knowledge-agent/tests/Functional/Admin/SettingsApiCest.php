<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

final class SettingsApiCest
{
    public function settingsApiReturnsDefaults(\FunctionalTester $I): void
    {
        $I->sendGet('/admin/knowledge/api/settings');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['encyclopedia_enabled' => '1']);
        $I->seeResponseContains('security_instructions');
    }

    public function settingsApiSavesEncyclopediaToggle(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/admin/knowledge/api/settings', ['encyclopedia_enabled' => '0']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'saved']);

        $I->sendGet('/admin/knowledge/api/settings');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['encyclopedia_enabled' => '0']);

        // Restore default
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/admin/knowledge/api/settings', ['encyclopedia_enabled' => '1']);
        $I->seeResponseCodeIs(200);
    }

    public function settingsApiSavesBaseInstructions(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/admin/knowledge/api/settings', [
            'base_instructions' => 'Custom extraction instructions for testing',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'saved']);

        $I->sendGet('/admin/knowledge/api/settings');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContains('Custom extraction instructions for testing');
    }

    public function settingsApiRejectsEmptyInstructions(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/admin/knowledge/api/settings', [
            'base_instructions' => '',
        ]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['error' => 'Базові інструкції не можуть бути порожніми']);
    }
}
