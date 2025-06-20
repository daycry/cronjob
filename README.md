[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=SYC5XDT23UZ5G&no_recurring=0&item_name=Thank+you%21&currency_code=EUR)

> [!NOTE]  
> If you want to use a queue system you can use [queues vendor](https://github.com/daycry/queues)

# CodeIgniter Job Scheduler

[![Build Status](https://github.com/daycry/cronjob/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/daycry/cronjob/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/daycry/cronjob/badge.svg?branch=master)](https://coveralls.io/github/daycry/cronjob?branch=master)
[![Downloads](https://poser.pugx.org/daycry/cronjob/downloads)](https://packagist.org/packages/daycry/cronjob)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/daycry/cronjob)](https://packagist.org/packages/daycry/cronjob)
[![GitHub stars](https://img.shields.io/github/stars/daycry/cronjob)](https://packagist.org/packages/daycry/cronjob)
[![GitHub license](https://img.shields.io/github/license/daycry/cronjob)](https://github.com/daycry/cronjob/blob/master/LICENSE)

Welcome! The documentation is now organized for easier reading:

- [Installation](docs/installation.md)
- [Dashboard](docs/dashboard.md)
- [Defining Schedules](docs/scheduling.md)
- [Job Dependencies](docs/dependencies.md)
- [Advanced Features](docs/advanced.md)
- [Metrics & Monitoring](docs/metrics-monitoring.md)

For full usage, examples, and integration details, see each section above.

---

## Quick Start

This makes scheduling cronjobs in your application simple, flexible, and powerful. Instead of setting up 
multiple cronjobs on each server your application runs on, you only need to setup a single cronjob to 
point to the script, and then all of your tasks are scheduled in your code. Besides that, it provides 
CLI tools to help you manage the tasks that should be ran, a Debug Toolbar collector, and more. 

## Installation via composer

Use the package with composer install

	> composer require daycry/cronjob

## Configuration

Run command:

	> php spark cronjob:publish

This command will copy a config file to your app namespace.
Then you can adjust it to your needs. By default file will be present in `app/Config/CronJob.php`.

    > php spark migrate -all

This command create rest server tables in your database.

## Starting the Scheduler

You only need to add a single line to your cronjob: 

    > * * * * * php /path-to-your-project/spark cronjob:run >> /dev/null 2>&1
    
This will call your script every minute. When `cronjob:run` is called, Tasks will determine the
correct tasks that should be run and execute them.

## Dashboard

You can access the web interface to view the status of jobs using the following url: `https://example.com/cronjob`
You must configure an username and password in CronJob.php Config file.

```php
    /*
    |--------------------------------------------------------------------------
    | Dashboard login
    |--------------------------------------------------------------------------
    */
    public string $username = 'admin';
    public string $password = 'admin';
```

## Custom Views

Go to app\Config\CronJob.php ->views['dashboard'] and put your view's path there, like: "Cronjob/dashboard" - which is located in app\Views\Cronjob\dashboard.php

```php
/*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public array $views = [
        'login'                       => '\Daycry\CronJob\Views\login',
        'dashboard'                   => '\Daycry\CronJob\Views\dashboard',
        'layout'                      => '\Daycry\CronJob\Views\layout',
        'logs'                        => '\Daycry\CronJob\Views\logs'
    ];
```

![CronJob List](/docs/images/cronjob-list.jpg)

## Defining Schedules

Tasks are configured with the `app/Config/CronJob.php` config file, inside of the `init()` method.
Lets start with a simple example: 

```
<?php namespace Daycry\CronJob\Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\CronJob\Scheduler;

class CronJob extends BaseConfig
{
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
        $schedule->call(function() { 
            DemoContent::refresh();
        })->everyMonday();
    }
}
```

In this example, we use a closure to refresh demo content at 12:00 am every Monday morning. Closures are 
a simple way to handle quick functions like this. You can also execute server commands, execute custom
CLI commands you have written, call a URL, or even fire off an Event of your choosing. Details are covered 
below.

### Scheduling CLI Commands

If you have written your own [CLI Commands](https://codeigniter.com/user_guide/cli/cli_commands.html), you 
can schedule them to run using the `command()` method.

```
$schedule->command('demo:refresh --all');
```  

The only argument is a string that calls the command, complete with an options or arguments. 

### Scheduling Shell Commands

You can call out to the server and execute a command using the `shell()` method.

```
$schedule->shell('cp foo bar')->daily( '11:00 pm' );
$schedule->shell('cp foo bar')->daily( '23:00' );
``` 

Simply provide the command to call and any arguments, and it will be executed using PHP's `exec()` method. 

> NOTE: Many shared servers turn off exec access for security reasons. If you will be running
> on a shared server, double-check you can use the exec command before using this feature.

### Scheduling Events

If you want to trigger an [Event](https://codeigniter.com/user_guide/extending/events.html) you can 
use the `event()` method to do that for you, passing in the name of the event to trigger.

```
$schedule->event('Foo')->hourly();
```

### Running Command In Background 

If you want to run a command in background, you can use the `runInBackground()` method to do that for you,
so the command won't block the execution of the next schedulers.

> [!Note]
> Currently only **commands** are able to run in background

```php
$schedule->command('slow-command')->runInBackground()->hourly();
```

It prevents the command from blocking the execution of the next schedulers.

### Running On One server

If your scheduler runs on multiple servers, you can determine if your scheduled job 
will executes in a single or in multiple servers using the `setRunType` method.

> [!Note]
> By default, the scheduled job will execute in multiple servers

```php
$schedule->event('Foo')->setRunType('multiple')->hourly(); // Runs in all servers
$schedule->event('Foo')->setRunType('single')->hourly(); // Runs in one server
```
This prevents duplication of tasks across servers by securing a lock on the job, 
ensuring it runs exclusively on the first server that obtains it.

### Scheduling URL Calls

If you need to ping a URL on a regular basis, you can use the `url()` method to perform a simple
GET request using cURL to the URL you pass in. If you need more dynamism than can be provided in 
a simple URL string, you can use a closure or command instead.

```
$schedule->url('https://my-status-cloud.com?site=foo.com')->everyFiveMinutes();
```

### Frequency Options

There are a number of ways available to specify how often the task is called.


| Method                        | Description                                                           |
|:------------------------------|:----------------------------------------------------------------------|
| ->cron('* * * * *')           | Run on a custom cron schedule.                                        |
| ->daily('4:00 am')            | Runs daily at 12:00am, unless a time string is passed in.             |    
| ->hourly() / ->hourly(15)     | Runs at the top of every hour or at specified minute.                 |
| ->everyFiveMinutes()          | Runs every 5 minutes (12:00, 12:05, 12:10, etc)                       |
| ->everyFifteenMinutes()       | Runs every 15 minutes (12:00, 12:15, etc)                             |
| ->everyThirtyMinutes()        | Runs every 30 minutes (12:00, 12:30, etc)                             |
| ->sundays('3:15 am')           | Runs every Sunday at midnight, unless time passed in.                 |
| ->mondays('3:15 am')           | Runs every Monday at midnight, unless time passed in.                 |
| ->tuesdays('3:15 am')          | Runs every Tuesday at midnight, unless time passed in.                |
| ->wednesdays('3:15 am')        | Runs every Wednesday at midnight, unless time passed in.              |
| ->thursdays('3:15 am')         | Runs every Thursday at midnight, unless time passed in.               |
| ->fridays('3:15 am')           | Runs every Friday at midnight, unless time passed in.                 |
| ->saturdays('3:15 am')         | Runs every Saturday at midnight, unless time passed in.               |
| ->monthly('12:21 pm')          | Runs the first day of every month at 12:00am unless time passed in.   |
| ->quarterly('5:00 am')         | Runs the first day of each quarter (Jan 1, Apr 1, July 1, Oct 1)      |
| ->yearly('12:34 am')           | Runs the first day of the year.                                       |
| ->weekdays('1:23 pm')          | Runs M-F at 12:00 am unless time passed in.                           |
| ->weekends('2:34 am')          | Runs Saturday and Sunday at 12:00 am unless time passed in.           |
| ->environments('local', 'prod')   | Restricts the task to run only in the specified environments      |
| ->everyHour(3, 15)            | Runs every 3 hours at XX:15.                                          |
| ->betweenHours(6,12)          | Runs between hours 6 and 12.                                          |
| ->hours([0,10,16])            | Runs at hours 0, 10 and 16.                                           |
| ->everyMinute(20)             | Runs every 20 minutes.                                                |
| ->betweenMinutes(0,30)        | Runs between minutes 0 and 30.                                        |
| ->minutes([0,20,40])          | Runs at specific minutes 0,20 and 40.                                 |
| ->days([0,3])                 | Runs only on Sunday and Wednesday  ( 0 is Sunday , 6 is Saturday )    |
| ->daysOfMonth([1,15])         | Runs only on days 1 and 15.                                           |
| ->months([1,7])               | Runs only on January and July.                                        |



These methods can be combined to create even more nuanced timings: 

```
$schdule->command('foo)
    ->weekdays()
    ->hourly()
    ->environments('development');
```

### Naming Tasks

You can name tasks so they can be easily referenced later, such as through the CLI with the `named()` method:

```
$schedule->command('foo')->hourly()->named('foo-task');
```

## Job Dependencies (NEW)

You can now define dependencies between jobs. A job will only run after all the jobs it depends on have been executed successfully in the same run. This is useful for workflows where certain tasks must be completed before others start.

### Usage Example

```php
$schedule->command('generate:report')->everyDay()->named('generate-report');
$schedule->command('send:report')->everyDay()->dependsOn('generate-report');
```

You can also specify multiple dependencies:

```php
$schedule->command('archive:report')->everyDay()->dependsOn(['generate-report', 'send-report']);
```

- The `dependsOn()` method accepts a string (job name) or an array of job names.
- Make sure to use the `named()` method to assign unique names to jobs you want to reference as dependencies.
- If there is a circular dependency or a missing job name, the scheduler will skip those jobs to prevent infinite loops.

## Advanced Features (NEW)

### Job Retries and Timeout

You can now configure automatic retries and a timeout for each job:

```php
$schedule->command('unstable:task')->maxRetries(3)->timeout(60); // Retries up to 3 times, 60s timeout
```
- `maxRetries(int)` sets how many times the job will be retried if it fails.
- `timeout(int)` sets the maximum execution time in seconds (enforced at the job logic level).

### Hooks: Before and After Job Execution

You can hook into the execution lifecycle of jobs by overriding the `beforeJob` and `afterJob` methods in a custom JobRunner:

```php
class MyJobRunner extends \Daycry\CronJob\JobRunner {
    protected function beforeJob($job) { /* ... */ }
    protected function afterJob($job, $result, $error) { /* ... */ }
}
```

This allows you to add custom logging, notifications, or metrics around job execution.

### Monitoring a Job (Example)

You can monitor the execution of a job by overriding the `afterJob` method in your custom `JobRunner` class. For example, you can send an alert if a job fails or takes too long:

```php
class MonitoringJobRunner extends \Daycry\CronJob\JobRunner {
    protected function afterJob($job, $result, $error) {
        $duration = $job->duration(); // Or use your own timing logic
        if ($error) {
            // Send alert to Slack, email, etc.
            log_message('alert', "Job '{$job->getName()}' failed: " . $error->getMessage());
        }
        if ($duration > 10) { // seconds
            // Alert if job took too long
            log_message('warning', "Job '{$job->getName()}' took {$duration}s to complete.");
        }
    }
}
```

You can also integrate with external monitoring tools (like Prometheus, Grafana, Sentry, etc.) by sending metrics or events from within `afterJob` or `reportMetrics`.

### Dependency Validation

The scheduler now validates that all dependencies exist and that there are no circular dependencies. If a dependency is missing or a cycle is detected, an exception is thrown during validation:

```php
$schedule->validateDependencies();
```

### Utility Methods for Scheduler

- `removeTaskByName($name)`: Remove a job by name.
- `hasTask($name)`: Check if a job exists by name.
- `getTaskNames()`: Get all job names.

## Job Execution Metrics (NEW)

The scheduler now measures and reports the execution time of each job. After running all jobs, you will see output like:

```
[METRIC] Job 'generate-report' average duration: 0.1234s
[METRIC] Job 'send-report' average duration: 0.0456s
```

You can customize the `reportMetrics` method in your `JobRunner` to log, store, or alert on these metrics as needed. This helps you identify slow jobs and performance bottlenecks in your scheduled tasks.

---

# CLI Commands

Included in the package are several commands that can be ran from that CLI that provide that bit of emergency
help you might need when something is going wrong with a cron job at a specific time.

All commands are ran through CodeIgniter's `spark` cli tool: 

    > php spark cronjob:list
    > php spark cronjob:run
    > php spark cronjob:run -testTime "2021-01-01 09:45:00"

Or if you prefer, you can run one or more specific jobs by passing the names separated by commas

    > php spark cronjob:run -only "foo-task,foo-task1"

## Available Commands

**cronjob:list**

    > php spark cronjob:list

This will list all available tasks that have been defined in the project, along with their type and
the next time they are scheduled to run.

    +--------------------------+---------+-------------+---------------------+---------------------+
    | Name                     | Type    | Expression  | Last Run            | Next Run            |
    +--------------------------+---------+-------------+---------------------+---------------------+
    | job1                     | command | 08 10 * * * | --                  | 2022-11-04 10:08:00 |
    | Job2                     | command | 0 0 * * *   | 2022-10-28 13:23:21 | 2022-11-05 00:00:00 |
    +--------------------------+---------+-------------+---------------------+---------------------+

**cronjob:disable**

    > php spark cronjob:disable 

Will disable the task runner manually until you enable it again. Writes a file to `{WRITEPATH}/cronJob` so 
you need to ensure that directory is writable. Default CodeIgniter permissions already have the WRITEABLE
path with write permissions. You should not need to change anything for this to work. 

**cronjob:enable**

    > php spark cronjob:enable

Will enable the task runner if it was previously disabled, allowing all tasks to resume running. 

**cronjob:run**

    > php spark cronjob:run
    
This is the primary entry point to the Tasks system. It should be called by a cron task on the server
every minute in order to be able to effectively run all of the scheduled tasks. You typically will not
run this manually.


## Notifications

If you want to receive notifications by email, simply configure the Codeigniter Email library.

## Monitoring Integration Examples

#### 1. Slack Alert Example

Send a message to a Slack channel if a job fails:

```php
class SlackMonitoringJobRunner extends \Daycry\CronJob\JobRunner {
    protected function afterJob($job, $result, $error) {
        if ($error) {
            $payload = json_encode([
                'text' => "Job '{$job->getName()}' failed: " . $error->getMessage()
            ]);
            $ch = curl_init('https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
```

#### 2. Sentry Error Reporting Example

Report job exceptions to Sentry:

```php
class SentryMonitoringJobRunner extends \Daycry\CronJob\JobRunner {
    protected function afterJob($job, $result, $error) {
        if ($error) {
            if (class_exists('Sentry\captureException')) {
                \Sentry\captureException($error);
            }
        }
    }
}
```

#### 3. Prometheus Metrics Example

Expose job duration as a Prometheus metric (using a PHP Prometheus client):

```php
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class PrometheusMonitoringJobRunner extends \Daycry\CronJob\JobRunner {
    protected $registry;
    public function __construct() {
        parent::__construct();
        $this->registry = new CollectorRegistry(new InMemory());
    }
    protected function afterJob($job, $result, $error) {
        $duration = $job->duration();
        $gauge = $this->registry->getOrRegisterGauge('cronjob', 'job_duration_seconds', 'Job duration in seconds', ['job']);
        $gauge->set($duration, [$job->getName()]);
    }
}
```
You can adapt these examples to your environment and needs. Refer to the official documentation of each tool for configuration and authentication details.
