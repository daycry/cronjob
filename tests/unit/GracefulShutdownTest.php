<?php

declare(strict_types=1);

use Daycry\CronJob\JobRunner;
use Tests\Support\TestCase;

final class GracefulShutdownTest extends TestCase
{
    public function testRequestStopHaltsFurtherJobs(): void
    {
        $scheduler = service('scheduler');
        $executed = [];
        $scheduler->call(function () use (&$executed) {
            $executed[] = 'a';
        })->named('job-a');
        $scheduler->call(function () use (&$executed) {
            $executed[] = 'b';
        })->named('job-b');

        $runner = new class () extends JobRunner {
            public function run(): void
            {
                parent::requestStop(); // simulate external signal before loop
                parent::run();
            }
        };

        $runner->run();
        $this->assertSame([], $executed, 'No jobs should run after stop requested before loop');
    }
}
