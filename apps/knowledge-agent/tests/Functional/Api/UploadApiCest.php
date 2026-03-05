<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

final class UploadApiCest
{
    public function testUploadRequiresAuth(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/api/v1/knowledge/upload', ['messages' => []]);
        $I->seeResponseCodeIs(401);
    }

    public function testUploadRejectsEmptyMessages(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Platform-Internal-Token', 'test-internal-token');
        $I->sendPost('/api/v1/knowledge/upload', ['messages' => []]);
        $I->seeResponseCodeIs(422);
    }

    public function testUploadRejectsMissingMessages(\FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-Platform-Internal-Token', 'test-internal-token');
        $I->sendPost('/api/v1/knowledge/upload', ['other' => 'data']);
        $I->seeResponseCodeIs(422);
    }
}
