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

    public static function forInvalidCommand(string $command)
    {
        return new self("Comando peligroso detectado: {$command}. Contiene caracteres o comandos no permitidos.");
    }

    public static function forCommandExecutionFailed(string $command, int $returnCode)
    {
        return new self("Error al ejecutar comando: {$command}. Código de salida: {$returnCode}");
    }

    public static function forInvalidUrl(string $url)
    {
        return new self("URL no válida o peligrosa: {$url}. Posible intento de SSRF.");
    }

    public static function forUrlRequestFailed(string $url, string $error)
    {
        return new self("Error al realizar petición HTTP a: {$url}. Error: {$error}");
    }
}
