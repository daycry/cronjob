<?php

namespace Daycry\CronJob\Exceptions;

use CodeIgniter\Exceptions\RuntimeException as BaseRuntimeException;

/**
 * Base library exception extending CodeIgniter's RuntimeException
 * so framework handlers (logging, HTTP converters, etc.) can treat
 * these uniformly.
 */
class CronJobException extends BaseRuntimeException
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
