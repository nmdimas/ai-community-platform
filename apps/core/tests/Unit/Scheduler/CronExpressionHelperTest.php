<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduler;

use App\Scheduler\CronExpressionHelper;
use Codeception\Test\Unit;

final class CronExpressionHelperTest extends Unit
{
    private CronExpressionHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new CronExpressionHelper();
    }

    public function testComputeNextRunReturnsDateTimeImmutable(): void
    {
        $next = $this->helper->computeNextRun('* * * * *');

        $this->assertInstanceOf(\DateTimeImmutable::class, $next);
        $this->assertGreaterThan(new \DateTimeImmutable('now'), $next);
    }

    public function testComputeNextRunHourlyExpression(): void
    {
        $next = $this->helper->computeNextRun('0 * * * *');

        $this->assertSame('00', $next->format('i'));
    }

    public function testComputeNextRunDailyExpression(): void
    {
        $next = $this->helper->computeNextRun('0 0 * * *');

        $this->assertSame('00:00', $next->format('H:i'));
    }

    public function testComputeNextRunWithTimezone(): void
    {
        $nextUtc = $this->helper->computeNextRun('0 12 * * *', 'UTC');
        $nextKyiv = $this->helper->computeNextRun('0 12 * * *', 'Europe/Kyiv');

        // Both should be valid DateTimeImmutable instances
        $this->assertInstanceOf(\DateTimeImmutable::class, $nextUtc);
        $this->assertInstanceOf(\DateTimeImmutable::class, $nextKyiv);
    }

    public function testIsValidReturnsTrueForValidExpression(): void
    {
        $this->assertTrue($this->helper->isValid('* * * * *'));
        $this->assertTrue($this->helper->isValid('0 * * * *'));
        $this->assertTrue($this->helper->isValid('0 0 * * *'));
        $this->assertTrue($this->helper->isValid('0 0 1 * *'));
        $this->assertTrue($this->helper->isValid('0 0 * * 0'));
    }

    public function testIsValidReturnsFalseForInvalidExpression(): void
    {
        $this->assertFalse($this->helper->isValid('invalid'));
        $this->assertFalse($this->helper->isValid('* * * *'));
        $this->assertFalse($this->helper->isValid('60 * * * *'));
    }
}
