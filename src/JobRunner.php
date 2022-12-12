<?php

namespace Daycry\CronJob;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use CodeIgniter\Config\BaseConfig;
use Config\Services;
use CodeIgniter\Email\Email;
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
    protected ?Datetime $testTime = null;

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
            if (!empty($this->only) && ! in_array($task->name, $this->only, true)) {
                continue;
            }

            if (!$task->shouldRun($this->testTime) && empty($this->only)) {
                continue;
            }

            $error  = null;
            $start  = Time::now();
            $output = null;

            $this->cliWrite('Processing: ' . ($task->name ?: 'Task'), 'green');

            try {
                // How many jobs are runned
                array_push($this->jobs, $task);

                $output = $task->run();

                if (!$output) {
                    $output = \ob_get_contents();
                }

                $this->cliWrite('Executed: ' . ($task->name ?: 'Task'), 'cyan');

                // @codeCoverageIgnoreStart
            } catch (\Throwable $e) {
                $this->cliWrite('Failed: ' . ($task->name ?: 'Task'), 'red');
                log_message('error', $e->getMessage(), $e->getTrace());
                $error = $e;
                // @codeCoverageIgnoreEnd
            } finally {
                $jobLog = new JobLog([ 'task' => $task, 'output' => $output, 'runStart' => $start, 'runEnd' => Time::now(), 'error' => $error, 'testTime' => $this->testTime ]);
                $this->storePerformanceLog($jobLog);

                if ($this->config->notification) {
                    // @codeCoverageIgnoreStart
                    $email = new Email();
                    $parser = Services::parser();

                    $email->setMailType('html');
                    $email->setFrom($this->config->from, $this->config->fromName);
                    $email->setTo($this->config->to, $this->config->toName);
                    $email->setSubject($parser->setData(array('job' => $task->name))->renderString(lang('CronJob.emailSubject')));
                    $email->setMessage($parser->setData(
                        array(
                            'name' => $task->name,
                            'runStart' => $start,
                            'duration' => $jobLog->duration(),
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
        $this->testTime = new DateTime($time);

        return $this;
    }


    /**
     * Performance log information is stored
     */
    protected function storePerformanceLog(JobLog $jobLog)
    {
        if (!$this->config->logPerformance) {
            return;
        }

        // "unique" name will be returned if one wasn't set
        $name = $jobLog->task->name;

        $data = [
            'name'     => $name,
            'type'     => $jobLog->task->getType(),
            'action'   => (\is_object($jobLog->task->getAction())) ? \json_encode($jobLog->task->getAction()) : $jobLog->task->getAction(),
            'environment' => \json_encode($jobLog->task->environments),
            'start_at'    => $jobLog->runStart->format('Y-m-d H:i:s'),
            'end_at' => $jobLog->runEnd->format('Y-m-d H:i:s'),
            'duration' => $jobLog->duration(),
            'output'   => $jobLog->output ?? null,
            'error'    => \json_encode($jobLog->error ?? null),
            'test_time' => ($this->testTime) ? $this->testTime->format('Y-m-d H:i:s') : null
        ];


        if ($this->config->logSavingMethod == 'database') {
            $logModel = new \Daycry\CronJob\Models\CronJobLogModel();

            if ($this->config->maxLogsPerJob) {
                $logs = $logModel->where('name', $name)->findAll();
                // Make sure we have room for one more
                if ((is_countable($logs) ? count($logs) : 0) >= $this->config->maxLogsPerJob) {
                    $forDelete = count($logs) - $this->config->maxLogsPerJob;
                    for ($i = 0; $forDelete >= $i; $i++) {
                        $logModel->delete($logs[$i]->id);
                    }
                }
            }

            $logModel->insert($data);
        } else {
            $path = $this->config->filePath . $name;
            $fileName = $path . '/' . $this->config->fileName . '.json';

            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            if (file_exists($fileName)) {
                $logs = \json_decode(\file_get_contents($fileName));
            } else {
                $logs = array();
            }

            // Make sure we have room for one more
            if ((is_countable($logs) ? count($logs) : 0) >= $this->config->maxLogsPerJob) {
                array_pop($logs);
            }

            // Add the log to the top of the array
            array_unshift($logs, $data);

            file_put_contents(
                $fileName,
                json_encode(
                    $logs,
                    JSON_PRETTY_PRINT
                )
            );
        }
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
