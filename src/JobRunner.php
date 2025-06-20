<?php

declare(strict_types=1);

namespace Daycry\CronJob;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Config\Services;
use DateTime;
use Daycry\CronJob\Config\CronJob as BaseConfig;
use Daycry\CronJob\Exceptions\TaskAlreadyRunningException;
use Exception;
use Throwable;

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
    }

    /**
     * Hook: called before a job is executed.
     * Override or extend for custom behavior.
     */
    protected function beforeJob(Job $job): void
    {
        // Custom logic or event dispatch (e.g., Events::trigger('cronjob.beforeJob', $job));
    }

    /**
     * Hook: called after a job is executed.
     * Override or extend for custom behavior.
     *
     * @param mixed $result
     */
    protected function afterJob(Job $job, $result, ?Throwable $error): void
    {
        // Custom logic or event dispatch (e.g., Events::trigger('cronjob.afterJob', $job, $result, $error));
    }

    /**
     * Mide y retorna el tiempo de ejecución de un job (en segundos).
     *
     * @return array [result, duration]
     */
    protected function measureJobExecution(callable $callback): array
    {
        $start    = microtime(true);
        $result   = $callback();
        $duration = microtime(true) - $start;

        return [$result, $duration];
    }

    /**
     * Runs all scheduled jobs, respecting dependencies, retries, and hooks.
     * Usa topological sort y mide tiempos de ejecución.
     */
    public function run(): void
    {
        $this->jobs = [];
        $order      = $this->scheduler->getExecutionOrder();
        $executed   = [];
        $metrics    = [];

        foreach ($order as $task) {
            if ($this->shouldSkipTask($task)) {
                continue;
            }
            $this->jobs[] = $task;
            $result       = null;
            $error        = null;
            $retries      = $task->getMaxRetries() ?? 0;
            $attempt      = 0;

            do {
                $attempt++;
                $this->beforeJob($task);
                try {
                    [$result, $duration] = $this->measureJobExecution(fn () => $this->processTask($task));
                    $metrics[$task->getName()][] = $duration;
                    $error = null;
                } catch (Throwable $e) {
                    $error = $e;
                    if ($attempt > $retries) {
                        $this->handleTaskError($task, $e);
                    } else {
                        continue; // Reintenta sin propagar la excepción
                    }
                }
                $this->afterJob($task, $result, $error);
            } while ($error && $attempt <= $retries);
            $executed[] = $task->getName();
        }
        $this->reportMetrics($metrics);
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
        $error  = null;
        $start  = Time::now();
        $output = null;

        $this->cliWrite('Processing: ' . ($task->getName() ?: 'Task'), 'green');
        $task->startLog();

        try {
            $this->validateTask($task);
            $output = $task->run() ?: \ob_get_contents();
            $this->cliWrite('Executed: ' . ($task->getName() ?: 'Task'), 'cyan');
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
            throw new Exception(($task->getName() ?: 'Task') . ' is disabled.', 100);
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
}
