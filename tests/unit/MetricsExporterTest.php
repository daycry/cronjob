<?php

declare(strict_types=1);

use Daycry\CronJob\JobRunner;
use Daycry\CronJob\Metrics\InMemoryExporter;
use Daycry\CronJob\Scheduler;
use Tests\Support\TestCase;

final class MetricsExporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure scheduler service is reset if framework caches it.
    }

    public function testRecordsSuccessfulAttempt()
    {
        $scheduler = service('scheduler');
        $job = $scheduler->call(static function () {
            usleep(10_000); // 10ms
        })->named('success_job');

        $exporter = new InMemoryExporter();
        $runner   = (new JobRunner())->withMetricsExporter($exporter);
        $runner->run();

        $data = $exporter->flush();
        $this->assertArrayHasKey('success_job', $data);
        $this->assertSame(1, $data['success_job']['attempts']);
        $this->assertSame(1, $data['success_job']['successes']);
        $this->assertSame(0, $data['success_job']['failures']);
    }

    public function testRecordsFailedRetries()
    {
        $scheduler = service('scheduler');
        $attempts = 0;
        $scheduler->call(function () use (&$attempts) {
            $attempts++;
            throw new RuntimeException('fail');
        })->named('failing_job')->maxRetries(2); // total 3 attempts

        $exporter = new InMemoryExporter();
        $runner   = (new JobRunner())->withMetricsExporter($exporter);
        try {
            $runner->run();
        } catch (Throwable $e) {
            // final failure bubbles after retries exhausted
        }
        $data = $exporter->flush();
        $this->assertArrayHasKey('failing_job', $data);
        $this->assertSame(3, $data['failing_job']['attempts']);
        $this->assertSame(0, $data['failing_job']['successes']);
        $this->assertSame(3, $data['failing_job']['failures']);
        $this->assertSame(3, $attempts);
    }
}
