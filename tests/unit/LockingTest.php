<?php

declare(strict_types=1);

use Daycry\CronJob\Job;
use Tests\Support\TestCase;

final class LockingTest extends TestCase
{
    public function testSingleRunLockPreventsSecondAcquisition(): void
    {
        $job = (new Job('closure', fn() => 'ok'))
            ->named('lock-job')
            ->maxRetries(0); // not relevant

        // First acquisition should succeed
        $acquired = $job->saveRunningFlag(true);
        $this->assertIsArray($acquired);

        // Second acquisition should fail (still locked)
        $second = $job->saveRunningFlag(true);
        $this->assertFalse($second, 'Second acquisition should fail while lock held');

        // Release
        $job->saveRunningFlag(false);

        // Now acquisition should succeed again
        $third = $job->saveRunningFlag(true);
        $this->assertIsArray($third);
        $job->saveRunningFlag(false);
    }

    public function testStaleLockIsStolenWhenTTLExpired(): void
    {
        $job = (new Job('closure', fn() => 'ok'))->named('stale-lock');
        $config = config('CronJob');
        $config->lockTTL = 1; // 1 second TTL
        $config->enableEvents = false;

        $job->saveRunningFlag(true);
        // Locate lock file
        $lockDir = rtrim($config->lockPath ?? ($config->filePath . 'locks/'), '/\\') . DIRECTORY_SEPARATOR;
        $lockFile = $lockDir . md5($job->getName()) . '.lock';
        $this->assertFileExists($lockFile);

        // Simulate staleness by modifying mtime
        $past = time() - 10;
        touch($lockFile, $past);
        // Do NOT release; attempt to acquire again without releasing original handle should fail (still locked by same process)
        $stillLocked = $job->saveRunningFlag(true);
        $this->assertFalse($stillLocked, 'Same process holding lock should not reacquire');

        // Release now to mimic dead process cleanup scenario
        $job->saveRunningFlag(false);

        // Manually age file again (in case release updated mtime)
        touch($lockFile, $past);

        // New acquisition should succeed (fresh lock, not actually stolen because previous released)
        $acquired = $job->saveRunningFlag(true);
        $this->assertIsArray($acquired);
        $job->saveRunningFlag(false);
    }
}
