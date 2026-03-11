<?php

declare(strict_types=1);

namespace App\Scheduler;

interface CronExpressionHelperInterface
{
    public function computeNextRun(string $cronExpression, string $timezone = 'UTC'): \DateTimeImmutable;

    public function isValid(string $cronExpression): bool;
}
