<?php

namespace Daycry\CronJob\Traits;

use CodeIgniter\I18n\Time;

/**
 * Trait ActivityTrait
 */
trait ActivityTrait
{
    use LogTrait;

    /**
     * Determines whether this task should be run now
     * according to its schedule and environment.
     */
    public function shouldRun(?Time $testTime = null): bool
    {
        $this->testTime = $testTime;
        // Are we restricting to environments?
        if (! empty($this->environments) && ! $this->runsInEnvironment($_SERVER['CI_ENVIRONMENT'])) {
            return false;
        }

        $cron = new \Cron\CronExpression($this->getExpression());

        $testTime = ($testTime) ?: 'now';

        return $cron->isDue($testTime, config('App')->appTimezone);
    }

    /**
     * Returns the date this was last ran.
     *
     * @return string|Time
     */
    public function lastRun()
    {
        if ($this->config->logPerformance === false) {
            return '--';
        }

        $name = ($this->name) ?: $this->buildName();

        $this->setHandler();

        return $this->handler->lastRun($name);
    }
}
