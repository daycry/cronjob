<?php

declare(strict_types=1);

namespace Tests\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use Daycry\CronJob\Job;
use Daycry\CronJob\Config\CronJob as CronJobConfig;
use Daycry\CronJob\Commands\Locks;
use CodeIgniter\CLI\CLI;

class LocksCommandTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure lock directory exists and is empty
        $config = config('CronJob');
        $lockPath = rtrim($config->lockPath, '/\\') . DIRECTORY_SEPARATOR;
        if (is_dir($lockPath)) {
            foreach (glob($lockPath . '*.lock') ?: [] as $f) { @unlink($f); }
        } else {
            mkdir($lockPath, 0777, true);
        }
    }

    public function testCommandShowsNoLocks(): void
    {
     $cmd = new Locks(service('logger'), service('commands'));
    $result = $cmd->run(['--force','--silent']);
     $this->assertIsString($result);
     $this->assertSame('No active locks.', $result);
    }

    public function testCommandListsOneLock(): void
    {
        $config = config('CronJob');
        $job = new Job('closure', static function() {});
        $job->named('test-lock');
        // Force single-run style lock
        $job->setRunType('single');
        $job->saveRunningFlag(true);

        $cmd = new Locks(service('logger'), service('commands'));
    $result = $cmd->run(['--force','--silent']);
        if (! is_array($result)) {
            $this->fail('Expected array of lock rows, got: ' . gettype($result));
        }
        $this->assertNotEmpty($result);
        /** @var array<int,array<string,mixed>> $result */
        $first = $result[0];
        $this->assertIsArray($first);
        $this->assertArrayHasKey('file', $first);
        // Clean up
        $job->saveRunningFlag(false);
    }

    public function testJsonModeNoLocks(): void
    {
        $cmd = new Locks(service('logger'), service('commands'));
        $result = $cmd->run(['--force','--json']);
    $this->assertIsArray($result, 'Expected array structure for JSON mode no-locks');
    $this->assertArrayHasKey('message', $result);
    $this->assertArrayHasKey('locks', $result);
    $this->assertSame('No active locks.', $result['message']);
    $this->assertIsArray($result['locks']);
    $this->assertCount(0, $result['locks']);
    }

    public function testJsonModeWithOneLock(): void
    {
        $job = new Job('closure', static function() {});
        $job->named('json-lock');
        $job->setRunType('single');
        $job->saveRunningFlag(true);

        $cmd = new Locks(service('logger'), service('commands'));
    $result = $cmd->run(['--force','--json']);
    $this->assertIsArray($result, 'Expected array of locks for JSON mode');
    $this->assertArrayHasKey('locks', $result);
    $this->assertNotEmpty($result['locks']);
    $first = $result['locks'][0];
        $this->assertArrayHasKey('file', $first);
        $job->saveRunningFlag(false);
    }
}
