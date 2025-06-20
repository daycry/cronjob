<?php

use Daycry\CronJob\Job;
use Daycry\CronJob\JobRunner;
use Tests\Support\TestCase;

class JobRunnerHooksTestable extends JobRunner
{
    public array $before = [];
    public array $after = [];
    protected function beforeJob(Job $job): void
    {
        $this->before[] = $job->getName();
    }
    protected function afterJob(Job $job, $result, ?\Throwable $error): void
    {
        $this->after[] = [$job->getName(), $result, $error];
    }
}

final class JobRunnerHooksTest extends TestCase
{
    public function testHooksAreCalled()
    {
        $job = (new Job('closure', function () {
            return 'done';
        }))->named('hook-job');
        $runner = new JobRunnerHooksTestable();
        $scheduler = service('scheduler');
        $this->setPrivateProperty($scheduler, 'tasks', [$job]);
        \Config\Services::injectMock('scheduler', $scheduler);
        $runner->run();
        $this->assertEquals(['hook-job'], $runner->before);
        $this->assertEquals([['hook-job', 'done', null]], $runner->after);
    }
}
