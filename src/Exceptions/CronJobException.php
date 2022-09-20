<?php

namespace Daycry\CronJob\Exceptions;

use RuntimeException;

class CronJobException extends RuntimeException
{
    public static function forInvalidTaskType(string $type)
    {
        return new self(lang('CronJob.invalidTaskType', [ $type ]));
    }

    public static function forInvalidExpression(string $type)
    {
        return new self(lang('CronJob.invalidExpression', [ $type ]));
    }
}
