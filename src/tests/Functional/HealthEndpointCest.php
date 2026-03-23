<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Tests public service health check functionality.
 *
 * @see docs/specs/api-protocol.md (Section: Health Checks)
 */
class HealthEndpointCest
{
    public function healthEndpointReturns200(\FunctionalTester $I): void
    {
        $I->sendGet('/health');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'ok',
            'service' => 'core-platform',
        ]);
    }

    public function healthEndpointRequiresNoAuthentication(\FunctionalTester $I): void
    {
        $I->sendGet('/health');
        $I->seeResponseCodeIs(200);
    }
}
