<?php

declare(strict_types=1);

namespace App\Tests\Functional\Wiki;

use App\Repository\SettingsRepository;

final class WikiToggleCest
{
    public function wikiReturns200WhenEnabled(\FunctionalTester $I): void
    {
        /** @var SettingsRepository $settings */
        $settings = $I->grabService(SettingsRepository::class);
        $settings->set('encyclopedia_enabled', '1');

        $I->sendGet('/wiki');
        $I->seeResponseCodeIs(200);
    }

    public function wikiReturns503WhenDisabled(\FunctionalTester $I): void
    {
        /** @var SettingsRepository $settings */
        $settings = $I->grabService(SettingsRepository::class);
        $settings->set('encyclopedia_enabled', '0');

        $I->sendGet('/wiki');
        $I->seeResponseCodeIs(503);
        $I->seeResponseContains('недоступна');

        // Cleanup
        $settings->set('encyclopedia_enabled', '1');
    }

    public function wikiEntryReturns503WhenDisabled(\FunctionalTester $I): void
    {
        /** @var SettingsRepository $settings */
        $settings = $I->grabService(SettingsRepository::class);
        $settings->set('encyclopedia_enabled', '0');

        $I->sendGet('/wiki/entry/fake-id');
        $I->seeResponseCodeIs(503);
        $I->seeResponseContains('недоступна');

        // Cleanup
        $settings->set('encyclopedia_enabled', '1');
    }
}
