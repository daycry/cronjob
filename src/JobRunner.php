<?php

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
 * Class TaskRunner
 */
class JobRunner
{
    protected $scheduler;
    protected ?Time $testTime = null;
    protected BaseConfig $config;
    protected array $jobs = [];
    protected array $only = [];

    public function __construct(?BaseConfig $config = null)
    {
        $this->config = $config ?: config('CronJob');
        $this->scheduler = service('scheduler');
    }

    public function run()
    {
        $this->jobs = [];
        $tasks = $this->scheduler->getTasks();

        foreach ($tasks as $task) {
            if ($this->shouldSkipTask($task)) {
                continue;
            }

            $this->jobs[] = $task;
        }

        foreach ($this->jobs as $task) {
            $this->processTask($task);
        }
    }

    protected function shouldSkipTask($task): bool
    {
        return (!empty($this->only) && !in_array($task->getName(), $this->only, true)) ||
               (!$task->shouldRun($this->testTime) && empty($this->only));
    }

    protected function processTask($task)
    {
        $error = null;
        $start = Time::now();
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
        } finally {
            $this->finalizeTask($task, $start, $output, $error);
        }
    }

    protected function validateTask($task)
    {
        if (!$task->saveRunningFlag(true) && $task->getRunType() === 'single') {
            throw new TaskAlreadyRunningException($task);
        }

        if (!$task->status()) {
            throw new Exception(($task->getName() ?: 'Task') . ' is disabled.', 100);
        }
    }

    protected function handleTaskError($task, Throwable $e)
    {
        $this->cliWrite('Failed: ' . ($task->getName() ?: 'Task'), 'red');
        log_message('error', $e->getMessage(), $e->getTrace());
    }

    protected function finalizeTask($task, Time $start, ?string $output, ?Throwable $error)
    {
        if ($task->shouldRunInBackground()) {
            return;
        }

        if (!$error instanceof TaskAlreadyRunningException) {
            $task->saveRunningFlag(false);
        }

        $task->saveLog($output, $error);
        $this->sendCronJobFinishesEmailNotification($task, $start, $output, $error);
    }

    public function sendCronJobFinishesEmailNotification(
        Job $task,
        Time $startAt,
        ?string $output = null,
        ?Throwable $error = null
    ): void {
        if (!$this->config->notification) {
            return;
        }

        $email = Services::email();
        $parser = Services::parser();

        $email->setMailType('html');
        $email->setFrom($this->config->from, $this->config->fromName);
        $email->setTo($this->config->to);
        $email->setSubject($parser->setData(['job' => $task->getName()])->renderString(lang('CronJob.emailSubject')));
        $email->setMessage($parser->setData([
            'name' => $task->getName(),
            'runStart' => $startAt,
            'duration' => $task->duration(),
            'output' => $output,
            'error' => $error,
        ])->render('Daycry\CronJob\Views\email_notification'));
        $email->send();
    }

    public function only(array $jobs = []): JobRunner
    {
        $this->only = $jobs;
        return $this;
    }

    public function getJobs(): array
    {
        return $this->jobs;
    }

    public function withTestTime(string $time): JobRunner
    {
        $this->testTime = Time::createFromInstance(new DateTime($time));
        return $this;
    }

    protected function cliWrite(string $text, ?string $foreground = null)
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'testing') {
            return;
        }

        if (!is_cli()) {
            return;
        }

        CLI::write('[' . date('Y-m-d H:i:s') . '] ' . $text, $foreground);
    }
}
