# Advanced Features

## Retries and Timeout

Configure automatic retries and a timeout for each job:

```php
$schedule->command('unstable:task')->maxRetries(3)->timeout(60); // Retries up to 3 times, 60s timeout
```

- `maxRetries(int)` sets how many times the job will be retried if it fails.
- `timeout(int)` sets the maximum execution time in seconds (enforced at the job logic level).

## Hooks: Before and After Job Execution

Override the `beforeJob` and `afterJob` methods in a custom JobRunner:

```php
class MyJobRunner extends \Daycry\CronJob\JobRunner {
    protected function beforeJob($job) { /* ... */ }
    protected function afterJob($job, $result, $error) { /* ... */ }
}
```

## Dependency Validation

The scheduler validates that all dependencies exist and that there are no circular dependencies:

```php
$schedule->validateDependencies();
```

## Utility Methods for Scheduler

- `removeTaskByName($name)`: Remove a job by name.
- `hasTask($name)`: Check if a job exists by name.
- `getTaskNames()`: Get all job names.
