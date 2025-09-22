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

    /**
     * Notification mode: 'always', 'on_error', 'never'
     * Default keeps prior behavior (always send when enabled).
     */
    public string $notificationMode = 'always';

    /**
     * Maximum number of characters from job output to store (null = unlimited)
     */
    public ?int $maxOutputLength = null;

    /**
     * --------------------------------------------------------------------------
     * Default Timeout (seconds)
     * --------------------------------------------------------------------------
     * A default execution timeout applied to jobs that do not explicitly
     * define one. Null disables global timeout.
     */
    public ?int $defaultTimeout = null; // e.g. 300

    /**
     * Backoff strategy for retries: 'none', 'fixed', 'exponential'
     */
    public string $retryBackoffStrategy = 'none';

    /**
     * Base seconds used when computing backoff delay (first retry delay).
     */
    public int $retryBackoffBase = 5;

    /**
     * Multiplier used for exponential strategy (delay = base * multiplier^(attempt-1)).
     */
    public float $retryBackoffMultiplier = 2.0;

    /**
     * Maximum delay cap (seconds) for any retry.
     */
    public int $retryBackoffMax = 300;

    /**
     * Whether to add +/- random jitter up to 15% of computed delay.
     */
    public bool $retryBackoffJitter = true;

    /**
     * Enable or disable internal event dispatching (cronjob.* events)
     */
    public bool $enableEvents = true;

    /**
     * Directory used to store lock files for single-run jobs.
     */
    public string $lockPath = WRITEPATH . 'cronJob/locks/';

    /**
     * Time-to-live (seconds) for a lock file before it's considered stale. Null disables TTL expiration.
     */
    public ?int $lockTTL = 3600;

    /**
     * Enable POSIX signal handling (graceful shutdown). Requires ext-pcntl and CLI context.
     */
    public bool $enableSignals = true;

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
    public string $username      = 'admin';
    public string $password      = 'admin';

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
