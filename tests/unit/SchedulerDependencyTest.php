<?php

use Daycry\CronJob\Scheduler;
use Daycry\CronJob\Job;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SchedulerDependencyTest extends TestCase
{
    public function testDetectsMissingDependency()
    {
        $scheduler = new Scheduler();
        $jobA = $scheduler->command('foo:bar')->named('A');
        $jobB = $scheduler->command('bar:baz')->named('B')->dependsOn('C');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Dependency 'C' for job 'B' does not exist.");
        $scheduler->validateDependencies();
    }

    public function testDetectsCircularDependency()
    {
        $scheduler = new Scheduler();
        $jobA = $scheduler->command('foo:bar')->named('A')->dependsOn('B');
        $jobB = $scheduler->command('bar:baz')->named('B')->dependsOn('A');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Circular dependency detected");
        $scheduler->validateDependencies();
    }

    public function testNoDependencyValidationError()
    {
        $scheduler = new Scheduler();
        $jobA = $scheduler->command('foo:bar')->named('A');
        $jobB = $scheduler->command('bar:baz')->named('B')->dependsOn('A');
        $scheduler->validateDependencies();
        $this->assertTrue(true); // If no exception, test passes
    }
}
