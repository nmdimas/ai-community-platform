<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class SearchApiCest
{
    public function testSearchRequiresQuery(\FunctionalTester $I): void
    {
        $I->sendGet('/api/v1/knowledge/search');
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
    }

    public function testSearchReturnsResults(\FunctionalTester $I): void
    {
        $I->sendGet('/api/v1/knowledge/search?q=test&mode=keyword');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['query' => 'test', 'mode' => 'keyword']);
    }

    public function testHealthEndpoint(\FunctionalTester $I): void
    {
        $I->sendGet('/health');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'ok', 'service' => 'knowledge-agent']);
    }
}
