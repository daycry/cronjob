<?php

declare(strict_types=1);

namespace Daycry\CronJob\Exceptions;

use Daycry\CronJob\Job;

class TimeoutException extends CronJobException
{
    public static function forJob(Job $job, int $timeout): self
    {
        return new self(sprintf('Job "%s" exceeded timeout of %d seconds', $job->getName(), $timeout));
    }
}
