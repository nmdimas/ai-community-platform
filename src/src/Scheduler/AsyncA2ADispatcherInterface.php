<?php

declare(strict_types=1);

namespace App\Scheduler;

interface AsyncA2ADispatcherInterface
{
    /**
     * Dispatch all jobs concurrently via non-blocking HTTP.
     *
     * @param list<array{id: string, skill_id: string, payload: array<string, mixed>, trace_id: string, request_id: string}> $jobs
     *
     * @return array<string, array{status: string, result?: array<string, mixed>, error?: string}>
     */
    public function dispatchAll(array $jobs): array;
}
