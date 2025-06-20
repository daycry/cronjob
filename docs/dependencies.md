# Job Dependencies

You can define dependencies between jobs. A job will only run after all the jobs it depends on have been executed successfully in the same run.

## Usage Example

```php
$schedule->command('generate:report')->everyDay()->named('generate-report');
$schedule->command('send:report')->everyDay()->dependsOn('generate-report');
$schedule->command('archive:report')->everyDay()->dependsOn(['generate-report', 'send-report']);
```

- Use `dependsOn()` with a string or array of job names.
- Use `named()` to assign unique names to jobs you want to reference as dependencies.
- The scheduler validates dependencies and detects cycles.
