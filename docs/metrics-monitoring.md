# Metrics and Monitoring

## Job Execution Metrics

The scheduler measures and reports the execution time of each job. After running all jobs, you will see output like:

```
[METRIC] Job 'generate-report' average duration: 0.1234s
[METRIC] Job 'send-report' average duration: 0.0456s
```

You can customize the `reportMetrics` method in your `JobRunner` to log, store, or alert on these metrics as needed.

## Monitoring a Job

Override the `afterJob` method in your custom `JobRunner` class to monitor jobs:

```php
class MonitoringJobRunner extends \Daycry\CronJob\JobRunner {
    protected function afterJob($job, $result, $error) {
        $duration = $job->duration();
        if ($error) {
            log_message('alert', "Job '{$job->getName()}' failed: " . $error->getMessage());
        }
        if ($duration > 10) {
            log_message('warning', "Job '{$job->getName()}' took {$duration}s to complete.");
        }
    }
}
```

## Monitoring Integration Examples

### Slack Alert Example
```php
// ...see README for full example...
```

### Sentry Error Reporting Example
```php
// ...see README for full example...
```

### Prometheus Metrics Example
```php
// ...see README for full example...
```
