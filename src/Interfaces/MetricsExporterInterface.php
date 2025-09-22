<?php

declare(strict_types=1);

namespace Daycry\CronJob\Interfaces;

/**
 * Metrics exporter abstraction so different backends (in-memory, Prometheus, etc.)
 * can be plugged without changing JobRunner internals.
 */
interface MetricsExporterInterface
{
    /**
     * Record a single job attempt.
     *
     * @param string $jobName   Unique job name
     * @param bool   $success   Whether the attempt succeeded
     * @param float  $duration  Attempt duration in seconds (fractional allowed)
     * @param int    $attempt   Attempt number starting at 1
     * @param bool   $final     True if no more retries will follow
     */
    public function recordAttempt(string $jobName, bool $success, float $duration, int $attempt, bool $final): void;

    /**
     * Flush/publish accumulated metrics. Return value is backend specific; may be ignored.
     * Should be idempotent and safe to call multiple times.
     *
     * @return mixed
     */
    public function flush(): mixed;
}
