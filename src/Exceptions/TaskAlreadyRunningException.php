<?php

namespace Daycry\CronJob\Exceptions;

use Daycry\CronJob\Job;

class TaskAlreadyRunningException extends CronJobException
{
    public function __construct(Job $task)
    {
        parent::__construct(
            sprintf('%s is single run task and one instance is already running.', $task->getName() ?: 'Task')
        );
    }
}