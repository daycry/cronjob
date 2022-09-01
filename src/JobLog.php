<?php

namespace Daycry\CronJob;

use Daycry\CronJob\Job;

class JobLog
{
    /**
     * @var Job
     */
    protected Job $task;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var \CodeIgniter\I18n\Time
     */
    protected $runStart;

    /**
     * @var \CodeIgniter\I18n\Time
     */
    protected $runEnd;

    /**
     * The exception thrown during execution, if any.
     *
     * @var \Throwable
     */
    protected $error;

    /**
     * TaskLog constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Returns the duration of the task in mm:ss format.
     *
     * @return string
     * @throws \Exception
     */
    public function duration()
    {
        $interval = $this->runEnd->diff($this->runStart);

        return $interval->format('%H:%I:%S');
    }

    /**
     * Magic getter
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            return $this->{ $key };
        }
    }
}
