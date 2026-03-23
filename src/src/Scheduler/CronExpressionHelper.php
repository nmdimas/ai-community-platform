<?php

declare(strict_types=1);

namespace App\Scheduler;

use Cron\CronExpression;

final class CronExpressionHelper implements CronExpressionHelperInterface
{
    public function computeNextRun(string $cronExpression, string $timezone = 'UTC'): \DateTimeImmutable
    {
        $cron = new CronExpression($cronExpression);
        $tz = new \DateTimeZone($timezone);
        $now = new \DateTime('now', $tz);

        $next = $cron->getNextRunDate($now, 0, false, $timezone);

        return \DateTimeImmutable::createFromMutable($next);
    }

    public function isValid(string $cronExpression): bool
    {
        return CronExpression::isValidExpression($cronExpression);
    }
}
