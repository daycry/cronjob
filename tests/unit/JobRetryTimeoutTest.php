<?php

use Daycry\CronJob\Job;
use Daycry\CronJob\JobRunner;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class JobRetryTimeoutTest extends TestCase
{
    public function testJobRetriesOnFailure()
    {
        $attempts = 0;
        $job = (new Job('closure', function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \RuntimeException('Fail');
            }
            return 'ok';
        }))->named('retry-job')->maxRetries(2);

        $runner = $this->getRunner([$job]);
        $runner->run();
        $this->assertEquals(3, $attempts);
    }

    public function testJobTimeoutProperty()
    {
        $job = (new Job('closure', function () {
            return 'ok';
        }))->named('timeout-job')->timeout(5);
        $this->assertEquals(5, $job->getTimeout());
    }

    private function getRunner(array $tasks = []): JobRunner
    {
        $scheduler = service('scheduler');
        $this->setPrivateProperty($scheduler, 'tasks', $tasks);
        \Config\Services::injectMock('scheduler', $scheduler);
        return new JobRunner();
    }
}
