<?php

namespace Daycry\CronJob\Exceptions;

use RuntimeException;

class CronJobException extends RuntimeException
{
    public static function forInvalidTaskType(string $type)
    {
        return new self(lang('CronJob.invalidTaskType', [$type]));
    }

    public static function forInvalidLogType()
    {
        return new self(lang('CronJob.invalidLogType'));
    }

    public static function forInvalidExpression(string $type)
    {
        return new self(lang('CronJob.invalidExpression', [$type]));
    }
}
