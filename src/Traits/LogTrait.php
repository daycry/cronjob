<?php

namespace Daycry\CronJob\Traits;

use CodeIgniter\I18n\Time;
use Daycry\CronJob\Exceptions\CronJobException;
use Daycry\CronJob\Interfaces\LoggerInterface;

/**
 * Trait LogTrait
 *
 * @package Daycry\CronJob
 */
trait LogTrait
{
    protected ?Time $start = null;
    protected ?Time $end = null;
    protected ?Time $testTime = null;
    protected ?LoggerInterface $handler = null;

    public function startLog()
    {
        $this->start = Time::now();
    }

    /**
     * @param ?string $output
     * @param ?string $error
     */
    public function saveLog(?string $output = null, ?string $error = null)
    {
        $this->name = ($this->name) ? $this->name : $this->buildName();

        if(setting('CronJob.logPerformance')) {
            $this->end = Time::now();

            $this->setHandler();

            $data = [
                'name'          => $this->name,
                'type'          => $this->getType(),
                'action'        => (\is_object($this->getAction())) ? \json_encode($this->getAction()) : $this->getAction(),
                'environment'   => $this->environments ? \json_encode($this->environments) : null,
                'start_at'      => $this->start->format('Y-m-d H:i:s'),
                'end_at'        => $this->end->format('Y-m-d H:i:s'),
                'duration'      => $this->duration(),
                'output'        => $output ? \json_encode($output) : null,
                'error'         => $error ? \json_encode($error) : null,
                'test_time'     => ($this->testTime) ? $this->testTime->format('Y-m-d H:i:s') : null
            ];

            $this->handler->save($data);
        }
    }

    public function getLogs()
    {
        if (setting('CronJob.logPerformance') === false) {
            return [];
        }

        $this->name = ($this->name) ? $this->name : $this->buildName();

        return $this->handler->getLogs($this->name);
    }

    public function duration()
    {
        $interval = $this->end->diff($this->start);

        return $interval->format('%H:%I:%S');
    }

    private function setHandler()
    {
        if(!setting('CronJob.logSavingMethod') || !array_key_exists(setting('CronJob.logSavingMethod'), setting('CronJob.logSavingMethodClassMap'))) {
            throw CronJobException::forInvalidLogType();
        }

        $class = setting('CronJob.logSavingMethodClassMap')[setting('CronJob.logSavingMethod')];
        $this->handler = new $class();
    }
}
