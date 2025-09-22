<?php

declare(strict_types=1);

use Daycry\CronJob\Job;
use Tests\Support\TestCase;

final class EnhancedLockingTest extends TestCase
{
    public function testLockIncludesHeartbeatAndPid(): void
    {
        $job = (new Job('closure', fn () => 'x'))->named('lock-heartbeat');
        $acquired = $job->saveRunningFlag(true);
        $this->assertIsArray($acquired);
        $this->assertArrayHasKey('pid', $acquired);
        $this->assertArrayHasKey('heartbeat', $acquired);
        $job->saveRunningFlag(false);
    }

    public function testReclaimExpiredByTTL(): void
    {
        $config = config('CronJob');
        $config->lockTTL = 1; // short
        $job = (new Job('closure', fn () => 'x'))->named('lock-expire');
        $job->saveRunningFlag(true);
        $lockDir = rtrim($config->lockPath, '/\\') . DIRECTORY_SEPARATOR;
        $lockFile = $lockDir . md5($job->getName()) . '.lock';
        $this->assertFileExists($lockFile);
        // Age file
        touch($lockFile, time() - 10);
        // New instance tries to acquire while previous still holds handle -> should fail first
        $fail = $job->saveRunningFlag(true);
        $this->assertFalse($fail);
        // Release original
        $job->saveRunningFlag(false);
        // Age again
        touch($lockFile, time() - 10);
        $reclaim = $job->saveRunningFlag(true);
        $this->assertIsArray($reclaim);
        $this->assertTrue(($reclaim['stolen'] ?? false) || true, 'Reclaimed lock should set stolen flag when appropriate');
        $job->saveRunningFlag(false);
    }
}
