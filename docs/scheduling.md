# Defining Schedules

Tasks are configured in the `app/Config/CronJob.php` config file, inside the `init()` method.

## Example

```php
public function init(Scheduler $schedule)
{
    $schedule->call(function() {
        DemoContent::refresh();
    })->everyMonday();
}
```

You can use closures, server commands, custom CLI commands, URLs, or events.

## Frequency Options

| Method                        | Description                                                           |
|:------------------------------|:----------------------------------------------------------------------|
| ->cron('* * * * *')           | Run on a custom cron schedule.                                        |
| ->daily('4:00 am')            | Runs daily at 12:00am, unless a time string is passed in.             |
| ->hourly() / ->hourly(15)     | Runs at the top of every hour or at specified minute.                 |
| ->everyFiveMinutes()          | Runs every 5 minutes (12:00, 12:05, 12:10, etc)                       |
| ...                           | ... (see full README for all options)                                 |

These methods can be combined for nuanced timings:

```php
$schedule->command('foo')
    ->weekdays()
    ->hourly()
    ->environments('development');
```

## Naming Tasks

You can name tasks for easy reference:

```php
$schedule->command('foo')->hourly()->named('foo-task');
```
