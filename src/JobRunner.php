<?php

namespace Daycry\CronJob;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use CodeIgniter\Config\BaseConfig;
use Config\Services;
use DateTime;

/**
 * Class TaskRunner
 *
 * @package CodeIgniter\Tasks
 */
class JobRunner
{
    /**
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * @var string
     */
    protected ?Time $testTime = null;

    /**
     * @var BaseConfig
     */
    protected BaseConfig $config;

    protected array $jobs = [];
    /**
     * Stores aliases of tasks to run
     * If empty, All tasks will be executed as per their schedule
     *
     * @var array
     */
    protected $only = [];

    public function __construct(BaseConfig $config = null)
    {
        $this->config = ($config) ? $config : config('CronJob');
        $this->scheduler = service('scheduler');
    }

    /**
     * The main entry point to run tasks within the system.
     * Also handles collecting output and sending out
     * notifications as necessary.
     */
    public function run()
    {
        $this->jobs = [];
        $tasks = $this->scheduler->getTasks();

        if ($tasks === []) {
            return;
        }

        foreach ($tasks as $task) {

            // If specific tasks were chosen then skip executing remaining tasks
            if (!empty($this->only) && ! in_array($task->getName(), $this->only, true)) {
                continue;
            }

            if (!$task->shouldRun($this->testTime) && empty($this->only)) {
                continue;
            }

            $error  = null;
            $start  = Time::now();
            $output = null;

            $this->cliWrite('Processing: ' . ($task->getName() ?: 'Task'), 'green');
            $task->startLog();

            try {
                // How many jobs are runned
                array_push($this->jobs, $task);

                if (!$task->saveRunningFlag(true) && $task->getRunType() == 'single') {
                    throw new \Exception(($task->getName() ?: 'Task') . ' is single run task and one instance already running.',100);
                }

                if (!$task->status()) {
                    throw new \Exception(($task->getName() ?: 'Task') . ' is disable.', 100);
                }

                $output = $task->run();

                if (!$output) {
                    $output = \ob_get_contents();
                }

                $this->cliWrite('Executed: ' . ($task->getName() ?: 'Task'), 'cyan');

                // @codeCoverageIgnoreStart
            } catch (\Throwable $e) {
                $this->cliWrite('Failed: ' . ($task->getName() ?: 'Task'), 'red');
                log_message('error', $e->getMessage(), $e->getTrace());
                $error = $e;
                // @codeCoverageIgnoreEnd
            } finally {
                if (!$error || ($error && strpos($error->getMessage(), 'is single run task and one instance already running') === false)) {
                    $task->saveRunningFlag(false);
                }

                $task->saveLog($output, $error);

                if (setting('CronJob.notification')) {
                    // @codeCoverageIgnoreStart
                    $email = Services::email();
                    $parser = Services::parser();

                    $email->setMailType('html');
                    $email->setFrom(setting('CronJob.from'), setting('CronJob.fromName'));
                    $email->setTo(setting('CronJob.to'), setting('CronJob.toName'));
                    $email->setSubject($parser->setData(array('job' => $task->getName()))->renderString(lang('CronJob.emailSubject')));
                    $email->setMessage($parser->setData(
                        array(
                            'name' => $task->getName(),
                            'runStart' => $start,
                            'duration' => $task->duration(),
                            'output' => $output,
                            'error' => $error
                        )
                    )->render('Daycry\CronJob\Views\email_notification'));
                    $email->send();
                    // @codeCoverageIgnoreEnd
                }
            }
        }
    }

    /**
     * Specify tasks to run
     *
     * @param array $jobs
     *
     * @return JobRunner
     */
    public function only(array $jobs = []): JobRunner
    {
        $this->only = $jobs;

        return $this;
    }

    /**
     * Get runned Jobs
     *
     * @return array
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Sets a time that will be used.
     * Allows setting a specific time to test against.
     * Must be in a DateTime-compatible format.
     *
     * @param string $time
     *
     * @return $this
     */
    public function withTestTime(string $time): JobRunner
    {
        $this->testTime = Time::createFromInstance(new DateTime($time));

        return $this;
    }

    /**
     * Write a line to command line interface
     *
     * @param string      $text
     * @param string|null $foreground
     */
    protected function cliWrite(String $text, String $foreground = null)
    {
        // Skip writing to cli in tests
        if (defined("ENVIRONMENT") && ENVIRONMENT === "testing") {
            return ;
        }

        if (!is_cli()) {
            return ;
        }

        CLI::write("[" . date("Y-m-d H:i:s") . "] " . $text, $foreground);
    }
}
