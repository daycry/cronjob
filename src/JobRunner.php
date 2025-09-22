<?php

declare(strict_types=1);

namespace Daycry\CronJob;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Config\Services;
use DateTime;
use Daycry\CronJob\Config\CronJob as BaseConfig;
use Daycry\CronJob\Exceptions\TaskAlreadyRunningException;
use Daycry\CronJob\Exceptions\TimeoutException;
use Daycry\CronJob\Interfaces\MetricsExporterInterface;
use Daycry\CronJob\Exceptions\CronJobException;
use Throwable;
use CodeIgniter\Events\Events;

/**
 * Class JobRunner
 *
 * Handles the execution and management of scheduled jobs.
 */
class JobRunner
{
    protected Scheduler $scheduler;
    protected ?Time $testTime = null;
    protected BaseConfig $config;
    protected ?MetricsExporterInterface $metricsExporter = null;
    /**
     * Internal flag indicating a graceful shutdown request.
     */
    private bool $stopRequested = false;

    /**
     * @var list<Job>
     */
    protected array $jobs = [];

    /**
     * @var list<string>
     */
    protected array $only = [];

    /**
     * JobRunner constructor.
     */
    public function __construct(?BaseConfig $config = null)
    {
        $this->config    = $config ?: config('CronJob');
        $this->scheduler = service('scheduler');
        $this->maybeRegisterSignalHandlers();
    }

    /**
     * Optionally inject a metrics exporter implementation.
     * Fluent so it can be chained in tests or setup code.
     */
    public function withMetricsExporter(MetricsExporterInterface $exporter): self
    {
        $this->metricsExporter = $exporter;
        return $this;
    }

    /**
     * Hook: called before a job is executed.
     * Override or extend for custom behavior.
     */
    protected function beforeJob(Job $job): void
    {
        // Default empty implementation; subclasses may override.
        // (Events are fired inside run() loop with correct attempt number.)
    }

    /**
     * Hook: called after a job is executed.
     * Override or extend for custom behavior.
     *
     * @param mixed $result
     */
    protected function afterJob(Job $job, $result, ?Throwable $error): void
    {
        // Default empty implementation; subclasses may override.
    }

    /**
     * Runs all scheduled jobs, respecting dependencies, retries, and hooks.
     * Usa topological sort y mide tiempos de ejecución.
     */
    public function run(): void
    {
        $this->jobs = [];
        $order      = $this->scheduler->getExecutionOrder();
        $metrics    = [];

        foreach ($order as $task) {
            if ($this->stopRequested) {
                $this->cliWrite('Graceful shutdown requested. Stopping further job dispatch.', 'yellow');
                break;
            }
            if ($this->shouldSkipTask($task)) {
                $this->fire('cronjob.skipped', [
                    'job'    => $task,
                    'reason' => 'filter_or_schedule',
                ]);
                continue;
            }
            $result       = null;
            $error        = null;
            $retries      = $task->getMaxRetries() ?? 0;
            $attempt      = 0;

            do {
                $attempt++;
                // Call subclass hook first
                $this->beforeJob($task);
                $this->fire('cronjob.beforeJob', [
                    'job'     => $task,
                    'attempt' => $attempt,
                ]);
                $attemptStart = microtime(true);
                $duration = null; // initialize
                try {
                    $result   = $this->processTask($task);
                    $duration = microtime(true) - $attemptStart;
                    $metrics[$task->getName()][] = $duration;
                    $error    = null;
                } catch (Throwable $e) {
                    $error = $e;
                    $duration = microtime(true) - $attemptStart;
                    // Also record failed attempt duration (helps diagnostics)
                    $metrics[$task->getName()][] = $duration;
                    if ($attempt > $retries) {
                        $this->handleTaskError($task, $e);
                        $this->fire('cronjob.failed', [
                            'job'      => $task,
                            'exception'=> $e,
                            'attempts' => $attempt,
                        ]);
                        // Fall-through to afterJob firing below
                    } else {
                        // Sleep with backoff before retrying if configured
                        $delay = $this->computeBackoffDelay($attempt);
                        if ($delay > 0) {
                            $this->cliWrite('Retrying ' . $task->getName() . ' in ' . $delay . 's', 'yellow');
                            $this->fire('cronjob.retryScheduled', [
                                'job'     => $task,
                                'attempt' => $attempt,
                                'delay'   => $delay,
                            ]);
                            sleep($delay);
                        }
                        // Fire afterJob for this failed attempt before continuing to next retry
                        $this->afterJob($task, $result, $error);
                        $this->fire('cronjob.afterJob', [
                            'job'     => $task,
                            'result'  => $result,
                            'error'   => $error,
                            'attempt' => $attempt,
                            'duration'=> $duration,
                        ]);
                        if ($this->metricsExporter) {
                            $this->metricsExporter->recordAttempt($task->getName(), false, $duration, $attempt, false);
                        }
                        continue; // Retry loop
                    }
                }
                // Call subclass hook first
                $this->afterJob($task, $result, $error);
                $this->fire('cronjob.afterJob', [
                    'job'     => $task,
                    'result'  => $result,
                    'error'   => $error,
                    'attempt' => $attempt,
                    'duration'=> $duration,
                ]);
                if ($this->metricsExporter) {
                    $this->metricsExporter->recordAttempt(
                        $task->getName(),
                        $error === null,
                        (float) $duration,
                        $attempt,
                        $error === null || $attempt >= ($task->getMaxRetries() ?? 0) + 1
                    );
                }
            } while ($error && $attempt <= $retries);
            // Only mark as executed after attempts complete (success or handled failure)
            $this->jobs[] = $task;
        }
        $this->reportMetrics($metrics);
        $this->fire('cronjob.metrics.flush', [
            'metrics'    => $metrics,
            'generatedAt'=> new \DateTimeImmutable(),
        ]);
        if ($this->metricsExporter) {
            // Give exporter a chance to publish. We ignore return to keep runner decoupled.
            $this->metricsExporter->flush();
        }
        if ($this->stopRequested) {
            $this->fire('cronjob.shutdown', [
                'when'     => new \DateTimeImmutable(),
                'executed' => array_map(static fn($j) => $j->getName(), $this->jobs),
            ]);
        }
    }

    /**
     * Reporta métricas de ejecución de jobs (puedes personalizar para logs, alertas, etc).
     */
    protected function reportMetrics(array $metrics): void
    {
        foreach ($metrics as $job => $runs) {
            $avg = array_sum($runs) / count($runs);
            $this->cliWrite("[METRIC] Job '{$job}' average duration: " . number_format($avg, 4) . 's', 'yellow');
        }
    }

    /**
     * Determines if a task should be skipped.
     *
     * @param Job $task
     */
    protected function shouldSkipTask($task): bool
    {
        return (! empty($this->only) && ! in_array($task->getName(), $this->only, true))
               || (! $task->shouldRun($this->testTime) && empty($this->only));
    }

    /**
     * Processes a single task and returns the result.
     *
     * @param Job $task
     *
     * @return mixed
     */
    protected function processTask($task)
    {
        $error    = null;
        $start    = Time::now();
        $output   = null;
        $timeout  = $task->getTimeout() ?? $this->config->defaultTimeout;
        $t0       = microtime(true);

        $this->cliWrite('Processing: ' . ($task->getName() ?: 'Task'), 'green');
        $task->startLog();

        try {
            $this->validateTask($task);
            $output = $task->run() ?: \ob_get_contents();
            $this->cliWrite('Executed: ' . ($task->getName() ?: 'Task'), 'cyan');
            if ($timeout && (microtime(true) - $t0) > $timeout) {
                $this->fire('cronjob.timeout', [
                    'job'           => $task,
                    'timeoutSeconds'=> $timeout,
                ]);
                throw TimeoutException::forJob($task, $timeout);
            }
        } catch (Throwable $e) {
            $this->handleTaskError($task, $e);
            $error = $e;

            throw $e;
        } finally {
            $this->finalizeTask($task, $start, $output, $error);
        }

        return $output;
    }

    /**
     * Compute delay (seconds) before next retry based on config and attempt number.
     * Attempt here is 1-based (i.e. first retry attempt number > 1 triggers backoff).
     */
    protected function computeBackoffDelay(int $attempt): int
    {
        // No delay before first run; only apply when attempt > 1
        if ($attempt <= 1) {
            return 0;
        }
        $strategy = $this->config->retryBackoffStrategy;
        if ($strategy === 'none') {
            return 0;
        }
        $base = max(1, $this->config->retryBackoffBase);
        $delay = $base;
        if ($strategy === 'fixed') {
            $delay = $base;
        } elseif ($strategy === 'exponential') {
            $multiplier = $this->config->retryBackoffMultiplier > 0 ? $this->config->retryBackoffMultiplier : 2.0;
            $delay      = (int) round($base * ($multiplier ** ($attempt - 2))); // attempt 2 => base * multiplier^0
        }
        $delay = min($delay, $this->config->retryBackoffMax);
        if ($this->config->retryBackoffJitter) {
            $jitterRange = (int) max(1, round($delay * 0.15));
            $delta       = random_int(-$jitterRange, $jitterRange);
            $delay       = max(1, $delay + $delta);
        }
        return $delay;
    }

    /**
     * Validates a task before execution.
     *
     * @param Job $task
     *
     * @throws Exception|TaskAlreadyRunningException
     */
    protected function validateTask($task): void
    {
        if (! $task->saveRunningFlag(true) && $task->getRunType() === 'single') {
            throw new TaskAlreadyRunningException($task);
        }
        if (! $task->status()) {
            throw new CronJobException(($task->getName() ?: 'Task') . ' is disabled.', 100);
        }
    }

    /**
     * Handles errors during task execution.
     *
     * @param Job $task
     */
    protected function handleTaskError($task, Throwable $e): void
    {
        $this->cliWrite('Failed: ' . ($task->getName() ?: 'Task'), 'red');
        log_message('error', $e->getMessage(), $e->getTrace());
    }

    /**
     * Finalizes a task after execution.
     *
     * @param Job $task
     */
    protected function finalizeTask($task, Time $start, ?string $output, ?Throwable $error): void
    {
        if ($task->shouldRunInBackground()) {
            return;
        }
        if (! $error instanceof TaskAlreadyRunningException) {
            $task->saveRunningFlag(false);
        }
        $task->saveLog($output, $error instanceof \Throwable ? $error->getMessage() : $error);
        $this->sendCronJobFinishesEmailNotification($task, $start, $output, $error);
    }

    /**
     * Sends an email notification when a job finishes.
     */
    public function sendCronJobFinishesEmailNotification(
        Job $task,
        Time $startAt,
        ?string $output = null,
        ?Throwable $error = null,
    ): void {
        if (! $this->config->notification) {
            return;
        }
        $email  = Services::email();
        $parser = Services::parser();
        $email->setMailType('html');
        $email->setFrom($this->config->from, $this->config->fromName);
        $email->setTo($this->config->to);
        $email->setSubject($parser->setData(['job' => $task->getName()])->renderString(lang('CronJob.emailSubject')));
        $email->setMessage($parser->setData([
            'name'     => $task->getName(),
            'runStart' => $startAt,
            'duration' => $task->duration(),
            'output'   => $output,
            'error'    => $error,
        ])->render('Daycry\CronJob\Views\email_notification'));
        $email->send();
    }

    /**
     * Restrict execution to only the specified jobs.
     *
     * @param list<string> $jobs
     *
     * @return $this
     */
    public function only(array $jobs = []): self
    {
        $this->only = $jobs;

        return $this;
    }

    /**
     * Get the list of jobs executed in this run.
     *
     * @return list<Job>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Set a test time for job execution (for testing purposes).
     *
     * @return $this
     */
    public function withTestTime(string $time): self
    {
        $this->testTime = Time::createFromInstance(new DateTime($time));

        return $this;
    }

    /**
     * Writes output to the CLI if running in CLI mode.
     */
    protected function cliWrite(string $text, ?string $foreground = null): void
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') {
            return;
        }
        if (! is_cli()) {
            return;
        }
        CLI::write('[' . date('Y-m-d H:i:s') . '] ' . $text, $foreground);
    }

    /**
     * Fire internal event if enabled. Exceptions from listeners are swallowed (logged at warning level).
     * @param array<string,mixed> $payload
     */
    private function fire(string $event, array $payload = []): void
    {
        if (! ($this->config->enableEvents ?? true)) {
            return;
        }
        try {
            Events::trigger($event, $payload);
        } catch (\Throwable $e) {
            log_message('warning', 'CronJob event listener error on ' . $event . ': ' . $e->getMessage());
        }
    }

    /**
     * Public API to request a graceful stop from user-land code (tests or external controller).
     */
    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    /**
     * Register POSIX signal handlers if enabled and environment supports it.
     */
    private function maybeRegisterSignalHandlers(): void
    {
        if (! ($this->config->enableSignals ?? false)) {
            return;
        }
        if (! function_exists('pcntl_signal')) {
            return; // extension not available
        }
        if (! is_cli()) {
            return; // only relevant in CLI context
        }
        // Use static to avoid multiple registrations
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }
        $handler = function (int $sig): void {
            $this->cliWrite('Received signal ' . $sig . ' -> initiating graceful shutdown', 'yellow');
            $this->stopRequested = true;
        };
        // Common termination signals
        if (defined('SIGTERM')) { @pcntl_signal(SIGTERM, $handler); }
        if (defined('SIGINT')) { @pcntl_signal(SIGINT, $handler); }
        if (defined('SIGQUIT')) { @pcntl_signal(SIGQUIT, $handler); }
    }
}
