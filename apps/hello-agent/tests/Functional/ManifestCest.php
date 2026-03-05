<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ManifestCest
{
    public function testManifestEndpointReturnsValidJson(\FunctionalTester $I): void
    {
        $I->sendGet('/api/v1/manifest');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'name' => 'hello-agent',
            'version' => '1.0.0',
            'capabilities' => [],
        ]);
    }

    public function testManifestContainsHealthUrl(\FunctionalTester $I): void
    {
        $I->sendGet('/api/v1/manifest');
        $I->seeResponseContainsJson(['health_url' => 'http://hello-agent/health']);
    }
}
