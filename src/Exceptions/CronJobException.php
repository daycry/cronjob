<?php

namespace Daycry\CronJob\Exceptions;

use RuntimeException;

class CronJobException extends RuntimeException
{
    public static function forInvalidTaskType(string $type)
    {
        return new static(lang('CronJob.invalidTaskType', [ $type ]));
    }

    public static function forInvalidExpression(string $type)
    {
        return new static(lang('CronJob.invalidExpression', [ $type ]));
    }
}
