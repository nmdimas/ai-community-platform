<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class HelloCest
{
    public function testHomepageRendersGreeting(\FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
        $I->see('Hello, World!');
    }
}
