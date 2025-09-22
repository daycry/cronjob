<?php

declare(strict_types=1);

namespace Daycry\CronJob\Metrics;

use Daycry\CronJob\Interfaces\MetricsExporterInterface;

/**
 * Simple in-memory exporter mainly for tests or debugging.
 */
class InMemoryExporter implements MetricsExporterInterface
{
    /** @var array<string,array<int,array{success:bool,duration:float,attempt:int,final:bool}>> */
    private array $attempts = [];

    public function recordAttempt(string $jobName, bool $success, float $duration, int $attempt, bool $final): void
    {
        $this->attempts[$jobName][] = [
            'success'  => $success,
            'duration' => $duration,
            'attempt'  => $attempt,
            'final'    => $final,
        ];
    }

    /**
     * Returns a structured snapshot and keeps data (non-destructive) so consecutive flush() calls are fine.
     *
     * @return array<string,mixed>
     */
    public function flush(): array
    {
        $result = [];
        foreach ($this->attempts as $job => $rows) {
            $totalDuration = 0.0;
            $successCount  = 0;
            foreach ($rows as $row) {
                $totalDuration += $row['duration'];
                if ($row['success']) {
                    $successCount++;
                }
            }
            $result[$job] = [
                'attempts'       => count($rows),
                'successes'      => $successCount,
                'failures'       => count($rows) - $successCount,
                'total_duration' => $totalDuration,
                'attempts_rows'  => $rows,
            ];
        }
        return $result;
    }

    /** For tests convenience only. */
    public function reset(): void
    {
        $this->attempts = [];
    }
}
