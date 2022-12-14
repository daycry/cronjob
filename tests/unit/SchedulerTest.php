<?php

use Daycry\CronJob\Scheduler;
use Daycry\CronJob\Job;
use CodeIgniter\Test\CIUnitTestCase as TestCase;

/**
 * @internal
 */
final class SchedulerTest extends TestCase
{
    /**
     * @var Scheduler
     */
    protected Scheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduler = new Scheduler();
    }

    public function testCallSavesTask()
    {
        $function = static function () {
            return 'Hello';
        };

        $job = $this->scheduler->call($function);

        $this->assertInstanceOf(\Closure::class, $function);
        $this->assertInstanceOf(Job::class, $job);
        $this->assertSame($function, $job->getAction());
        $this->assertSame('Hello', $job->getAction()());
    }

    public function testCommandSavesTask()
    {
        $job = $this->scheduler->command('foo:bar');

        $this->assertInstanceOf(Job::class, $job);
        $this->assertSame('foo:bar', $job->getAction());
    }

    public function testShellSavesTask()
    {
        $job = $this->scheduler->shell('foo:bar');

        $this->assertInstanceOf(Job::class, $job);
        $this->assertSame('foo:bar', $job->getAction());
    }
}
