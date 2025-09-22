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

## Configuration Options (New)

Below are additional configuration properties you can set in `Config\CronJob`.

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `maxOutputLength` | int\|null | null | Truncates stored output (and error) to this many characters, appending a truncation marker. Null = unlimited. |
| `notificationMode` | string | `always` | One of `always`, `on_error`, `never`. Controls email sending when `notification=true`. (Current implementation behaves as `always`; `on_error`/`never` reserved for future logic.) |
| `defaultTimeout` | int\|null | null | Global fallback timeout (seconds) applied when a job has no explicit `timeout()`. |
| `retryBackoffStrategy` | string | `none` | One of `none`, `fixed`, `exponential`. Governs delay between failed attempts. |
| `retryBackoffBase` | int | 5 | Base delay (seconds) used for `fixed` and as the starting point for `exponential`. |
| `retryBackoffMultiplier` | float | 2.0 | Exponential growth factor: delay = base * multiplier^(attempt-2). |
| `retryBackoffMax` | int | 300 | Maximum cap (seconds) for any computed backoff delay. |
| `retryBackoffJitter` | bool | true | Adds ±15% random jitter to the computed delay (helps avoid thundering herd). |
| `enableEvents` | bool | true | Enables internal lifecycle events (`cronjob.*`). Disable for absolute minimal overhead. |
| `lockPath` | string | WRITEPATH . 'cronJob/locks/' | Directory where single-run lock files are stored. |
| `lockTTL` | int\|null | 3600 | Seconds before a lock is considered stale and eligible for reclaim. Null disables TTL expiration. |
| `enableSignals` | bool | true | Enables POSIX signal handling (SIGTERM/SIGINT/SIGQUIT) to allow graceful shutdown mid-run (requires CLI + ext-pcntl). |

### Backoff Example

```php
// config/CronJob.php
public string $retryBackoffStrategy = 'exponential';
public int $retryBackoffBase = 3;      // first retry waits ~3s
public float $retryBackoffMultiplier = 2.0; // next ~6s, then ~12s ... capped
public int $retryBackoffMax = 60;      // cap at 60s
public bool $retryBackoffJitter = true; // add jitter
```

```php
$schedule->command('fragile:sync')->maxRetries(4); // up to 4 attempts with backoff
```

### Output Truncation Example

```php
// config/CronJob.php
public ?int $maxOutputLength = 2000; // keep logs light
```

Stored output beyond 2000 characters will end with:

```
[truncated 5234 -> 2000 chars]
```

### Timeout Behavior

Timeouts are “soft”: the job's runtime is measured; if it exceeds the configured seconds, a `TimeoutException` is thrown after completion of the execution block. For hard termination you would need to externalize the process (not implemented yet).

```php
$schedule->command('reports:build')->timeout(120); // overrides defaultTimeout
```

If neither per-job `timeout()` nor `defaultTimeout` are set, no timeout check is applied.

### Disabling Events

```php
public bool $enableEvents = false; // turn off all cronjob.* dispatches
```

This is useful in high-throughput or test scenarios where you need the absolute minimum overhead.

## Enhanced Locking & Single-Run Jobs

Single-run jobs (those whose `getRunType()` returns `single`) use a file lock to prevent concurrent execution. The locking mechanism now stores JSON metadata inside each lock file:

```json
{
    "flag": true,
    "time": "2025-09-15 12:34:56",
    "job": "my:example:job",
    "pid": 12345,
    "heartbeat": "2025-09-15T12:34:56+00:00",
    "stolen": true
}
```

Fields:
- `flag`: Internal marker (true while held).
- `time`: Acquisition timestamp (server local time).
- `job`: Resolved job name that owns the lock.
- `pid`: Process ID that created the lock (not set on Windows for liveness probing, but still recorded if available).
- `heartbeat`: ISO-8601 timestamp written at acquisition time (future versions may periodically refresh it to detect hung processes more precisely).
- `stolen` (optional): Present and set to true when a new process reclaimed an expired or dead lock.

### Reclaim Logic

When a process attempts to acquire a lock and it is already held, it will inspect the existing file. The lock is considered reclaimable when ANY of the following is true:
1. TTL Expired: Current time - file modification time > `lockTTL`.
2. Dead PID: On POSIX systems (non-Windows) if the stored PID does not respond to `posix_kill($pid, 0)`.
3. Heartbeat Stale: (Reserved) If heartbeat age > `lockTTL` (currently equivalent to TTL since heartbeat is only written once).

If reclaimable, the runner force-acquires the lock, truncates the file, and writes new metadata including `stolen: true`.

### Operational Guidance
- Keep `lockTTL` comfortably larger than your longest expected execution time to avoid premature steals.
- Set `lockTTL` to `null` to disable automatic stealing (a crashed process may then require manual cleanup).
- You can introspect locks by viewing the JSON files under `lockPath`.

## Graceful Shutdown

Long-running batches can now exit cleanly on demand.

Two mechanisms set an internal stop flag checked between jobs:
1. Signals (if `enableSignals=true`, CLI + ext-pcntl): SIGTERM, SIGINT, SIGQUIT.
2. Programmatic call: `$runner->requestStop();` from user code (tests, admin command, etc.).

When triggered:
- The current job continues until completion (no hard kill).
- No further jobs from the schedule are started.
- Event `cronjob.shutdown` is fired with payload: `when` (DateTimeImmutable) and `executed` (array of job names already run).

Example:

```php
$runner = new \Daycry\CronJob\JobRunner();
$runner->run(); // Press Ctrl+C or send SIGTERM to stop after current job
```

Or programmatically:

```php
$runner = (new \Daycry\CronJob\JobRunner());
// elsewhere, perhaps another thread/test hook
$runner->requestStop();
```

Disable completely (no signal handlers) by setting in `config/CronJob.php`:

```php
public bool $enableSignals = false;
```

Future enhancements may include per-job cooperative cancellation checks.

## Event System

When `enableEvents` is true, the runner dispatches lifecycle events through CodeIgniter's `Events` system. Each event receives a single associative array payload.

| Event Name | Fired When | Payload Keys |
|------------|------------|--------------|
| `cronjob.beforeJob` | Before each attempt | `job`, `attempt` |
| `cronjob.afterJob` | After an attempt finishes (success or failure) | `job`, `result`, `error`, `attempt`, `duration` |
| `cronjob.retryScheduled` | A retry will occur after backoff | `job`, `attempt`, `delay` |
| `cronjob.failed` | Final failure (no retries left) | `job`, `exception`, `attempts` |
| `cronjob.skipped` | Job filtered out (env/frequency/only filter) | `job`, `reason` |
| `cronjob.timeout` | Timeout exceeded (soft) | `job`, `timeoutSeconds` |
| `cronjob.metrics.flush` | After all jobs executed | `metrics`, `generatedAt` |
| `cronjob.shutdown` | Graceful stop requested (after loop ends) | `when`, `executed` |

### Listener Examples

```php
use CodeIgniter\Events\Events;

Events::on('cronjob.beforeJob', static function(array $data) {
    log_message('debug', 'Starting job ' . $data['job']->getName() . ' attempt=' . $data['attempt']);
});

Events::on('cronjob.afterJob', static function(array $data) {
    $status = $data['error'] ? 'FAILED' : 'OK';
    log_message('info', sprintf(
        'Finished %s status=%s duration=%.4fs attempt=%d',
        $data['job']->getName(),
        $status,
        $data['duration'] ?? 0,
        $data['attempt']
    ));
});

Events::on('cronjob.retryScheduled', static function(array $data) {
    log_message('warning', 'Retrying ' . $data['job']->getName() . ' in ' . $data['delay'] . 's (attempt ' . $data['attempt'] . ')');
});

Events::on('cronjob.failed', static function(array $data) {
    log_message('error', 'Job failed: ' . $data['job']->getName() . ' attempts=' . $data['attempts'] . ' error=' . $data['exception']->getMessage());
});
```

### Metrics Payload Structure

`metrics` is an associative array: `jobName => [runDurationAttempt1, runDurationAttempt2, ...]` (each element is seconds as float). You can aggregate averages, percentiles, or export to an external monitoring system.

### Notes

- Listeners should be fast; offload heavy processing to queues.
- Exceptions inside listeners are caught and logged (they will not break the scheduler).
- Disable events (`enableEvents=false`) if micro‑optimizing throughput or running in a constrained environment.

## Metrics Exporting

The scheduler can record per-attempt execution metrics via a pluggable exporter implementing `Daycry\\CronJob\\Interfaces\\MetricsExporterInterface`.

### Interface

```php
interface MetricsExporterInterface
{
    public function recordAttempt(string $jobName, bool $success, float $duration, int $attempt, bool $final): void;
    public function flush(): mixed; // publish / snapshot
}
```

### Built-in In-Memory Exporter

For testing or debugging you can use `Daycry\\CronJob\\Metrics\\InMemoryExporter`:

```php
$exporter = new \Daycry\CronJob\Metrics\InMemoryExporter();
$runner   = (new \Daycry\CronJob\JobRunner())
    ->withMetricsExporter($exporter);
$runner->run();
$snapshot = $exporter->flush();
// $snapshot structure:
// [
//   'jobName' => [
//       'attempts' => 2,
//       'successes' => 1,
//       'failures' => 1,
//       'total_duration' => 0.1534,
//       'attempts_rows' => [
//           ['success' => false, 'duration' => 0.05, 'attempt' => 1, 'final' => false],
//           ['success' => true,  'duration' => 0.10, 'attempt' => 2, 'final' => true],
//       ],
//   ],
// ]
```

Each attempt (including failures and retries) is captured with:
- `success`: Whether the attempt ended without exception.
- `duration`: Seconds (float) taken by the attempt.
- `attempt`: 1-based attempt number.
- `final`: True if this attempt ends the retry cycle (success or max retries reached).

### Events vs Exporter

The legacy `cronjob.metrics.flush` event still delivers an array of raw durations per job. The exporter is richer (captures per-attempt success flags) and is ideal for structured backends.

### Prometheus (Optional)

If you install `promphp/prometheus_client_php` (see composer suggest) you can implement a custom exporter that maps:
- Counter: total attempts by job & result
- Counter: failures by job
- Histogram/Summary: execution duration seconds

Skeleton:

```php
use Daycry\CronJob\Interfaces\MetricsExporterInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class PrometheusExporter implements MetricsExporterInterface
{
    private CollectorRegistry $registry;
    private $attemptCounter;
    private $durationHistogram;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
        $this->attemptCounter = $registry->getOrRegisterCounter('cronjob', 'attempts_total', 'Job attempts', ['job','result']);
        $this->durationHistogram = $registry->getOrRegisterHistogram('cronjob', 'duration_seconds', 'Job attempt duration', ['job']);
    }

    public function recordAttempt(string $jobName, bool $success, float $duration, int $attempt, bool $final): void
    {
        $this->attemptCounter->inc([$jobName, $success ? 'success' : 'failure']);
        $this->durationHistogram->observe($duration, [$jobName]);
    }

    public function flush(): mixed
    {
        // Expose metrics endpoint elsewhere (e.g. HTTP controller)
        return null;
    }
}
```

Then wire it:

```php
$runner = (new \Daycry\CronJob\JobRunner())
    ->withMetricsExporter(new PrometheusExporter($registry));
$runner->run();
```

### Choosing an Approach

| Use Case | Recommendation |
|----------|---------------|
| Local debugging | InMemoryExporter |
| Unit / CI assertions | InMemoryExporter snapshot |
| Production monitoring | Custom Prometheus / external exporter |
| Minimal overhead | Disable events + lightweight exporter or none |

If no exporter is provided, the system still logs average duration and fires the metrics event—no breaking change.

## Troubleshooting (Locks & Execution)

Problem | Symptoms | Likely Cause | Solution
--------|----------|-------------|---------
Lock not released | Single-run job never re-executes | Previous process died and `lockTTL = null` | Remove the file under `lockPath` or configure a `lockTTL`.
Frequent stolen locks (`stolen:true`) | Logs show many steals | `lockTTL` too short for real duration | Increase `lockTTL` (e.g. 3600 -> 10800).
PID present but process gone | Lock persists with dead PID | Abrupt restart / kill -9 | Enable TTL so it can be reclaimed.
Unexpected timeouts | `TimeoutException` thrown | `defaultTimeout` too low | Raise `defaultTimeout` or per-job `timeout()`.
Too many retries | Many `retryScheduled` entries | Very low backoff base with exponential | Increase `retryBackoffBase` or reduce `maxRetries`.
Slow events | Total run length grows | Heavy listener logic | Move heavy work to queue or disable events.

Additional tips:
- Keep `lockPath` on fast local storage (avoid high-latency network mounts when possible).
- In ephemeral containers, ensure `lockPath` is persisted to avoid parallel re-runs after restarts.
- For debugging, open the JSON file and inspect `time`, `pid`, `stolen`.

### Shutdown Listener Example (`cronjob.shutdown`)

```php
use CodeIgniter\Events\Events;

Events::on('cronjob.shutdown', static function(array $data) {
    log_message(
        'info',
        'Scheduler clean stop at ' . $data['when']->format('c') .
        ' (jobs executed: ' . implode(', ', $data['executed']) . ')'
    );
});
```

### CLI Lock Inspection Modes

Command: `php spark cronjob:locks`

Flags:
- `--force`: Run even if the CronJob system is currently disabled (bypasses status check).
- `--silent`: Suppress human-readable table output; only returns data to the caller (useful in tests).
- `--json`: Return a structured array (and suppress table) containing:
  - When no locks: `['message' => 'No active locks.', 'locks' => []]`
  - When locks exist: `['locks' => [ {job,file,pid,stolen,age_s,heartbeat,time}, ... ], 'count' => N]`

Example output (table mode):

```
+-----+-------------------------------+------+--------+-------+-----------+----------+
| Job | File                          | PID  | Stolen | Age(s)| Heartbeat | Acquired |
+-----+-------------------------------+------+--------+-------+-----------+----------+
| foo | 851abaafd3a69.lock            | 1234 | no     | 2     | 2025-09.. | 2025-09..|
+-----+-------------------------------+------+--------+-------+-----------+----------+
```

JSON mode (programmatic):

```php
$result = command('cronjob:locks --json --force');
// $result example:
// [
//   'locks' => [
//       [
//           'job' => 'foo',
//           'file' => '851abaafd3a69.lock',
//           'pid' => 1234,
//           'stolen' => 'no',
//           'age_s' => 2,
//           'heartbeat' => '2025-09-22T10:11:12+00:00',
//           'time' => '2025-09-22 10:11:12'
//       ]
//   ],
//   'count' => 1
// ]
```

