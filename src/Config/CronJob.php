<?php

namespace Daycry\CronJob\Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\CronJob\Loggers\Database as DatabaseLogger;
use Daycry\CronJob\Loggers\File as FileLogger;
use Daycry\CronJob\Scheduler;

class CronJob extends BaseConfig
{
    /**
     * Set true if you want save logs
     */
    public bool $logPerformance = true;

    /*
    |--------------------------------------------------------------------------
    | Log Saving Method
    |--------------------------------------------------------------------------
    |
    | Set to specify the REST API requires to be logged in
    |
    | 'file'   Save in files
    | 'database'  Save in database
    |
    */
    public string $logSavingMethod        = 'file';
    public array $logSavingMethodClassMap = [
        'file'     => FileLogger::class,
        'database' => DatabaseLogger::class,
    ];

    /**
     * Directory
     */
    public string $filePath = WRITEPATH . 'cronJob/';

    /**
     * File Name in folder jobs structure
     */
    public string $fileName = 'jobs';

    /**
     * --------------------------------------------------------------------------
     * Maximum performance logs
     * --------------------------------------------------------------------------
     *
     * The maximum number of logs that should be saved per Job.
     * Lower numbers reduced the amount of database required to
     * store the logs.
     *
     * If you write 0 it is unlimited
     */
    public int $maxLogsPerJob = 3;

    /*
    |--------------------------------------------------------------------------
    | Database Group
    |--------------------------------------------------------------------------
    |
    | Connect to a database group for logging, etc.
    |
    */
    public ?string $databaseGroup = null;

    /*
    |--------------------------------------------------------------------------
    | Cronjob Table Name
    |--------------------------------------------------------------------------
    |
    | The table name in your database that stores cronjobs
    |
    */
    public string $tableName = 'cronjob';

    /*
    |--------------------------------------------------------------------------
    | Cronjob Notification
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public bool $notification = false;
    public string $from       = 'your@example.com';
    public string $fromName   = 'CronJob';
    public string $to         = 'your@example.com';
    public string $toName     = 'User';

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public array $views = [
        'login'     => '\Daycry\CronJob\Views\login',
        'dashboard' => '\Daycry\CronJob\Views\dashboard',
        'layout'    => '\Daycry\CronJob\Views\layout',
        'logs'      => '\Daycry\CronJob\Views\logs',
    ];

    /*
    |--------------------------------------------------------------------------
    | Dashboard login
    |--------------------------------------------------------------------------
    */
    public bool $enableDashboard = false;

    // SECURITY: No usar credenciales por defecto en producción
    // Estas credenciales deben ser cambiadas obligatoriamente
    public string $username = '';  // Debe configurarse en el archivo de configuración local
    public string $password = '';  // Debe configurarse en el archivo de configuración local

    // Configuraciones de seguridad adicionales
    public int $maxLoginAttempts = 5;  // Máximo intentos de login
    public int $lockoutTime = 300;     // Tiempo de bloqueo en segundos (5 minutos)
    public bool $enableCSRFProtection = true;  // Protección CSRF
    public int $sessionTimeout = 3600; // Timeout de sesión en segundos (1 hora)

    /*
    |--------------------------------------------------------------------------
    | Cronjobs
    |--------------------------------------------------------------------------
    |
    | Register any tasks within this method for the application.
    | Called by the TaskRunner.
    |
    | @param Scheduler $schedule
    */
    public function init(Scheduler $schedule)
    {
        // $schedule->command('foo:bar')->everyMinute();

        // $schedule->shell('cp foo bar')->daily( '11:00 pm' );

        // $schedule->call( function() { do something.... } )->everyMonday()->named( 'foo' )
    }
}
