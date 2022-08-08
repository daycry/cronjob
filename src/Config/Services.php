<?php

namespace Daycry\CronJob\Config;

use Daycry\CronJob\CronExpression;
use Config\Services as BaseServices;
use Daycry\CronJob\Scheduler;

class Services extends BaseServices
{
    /**
     * Returns the Task Scheduler
     *
     * @param boolean $getShared
     *
     * @return \Daycry\CronJob\Scheduler
     */
    public static function scheduler(bool $getShared = true): Scheduler
    {
        if ($getShared) {
            return static::getSharedInstance('scheduler');
        }

        return new Scheduler();
    }

    /**
     * Returns the CronExpression class.
     *
     * @param boolean $getShared
     *
     * @return \Daycry\CronJob\CronExpression
     */
    public static function cronExpression(bool $getShared = true): CronExpression
    {
        if ($getShared) {
            return static::getSharedInstance('cronExpression');
        }

        return new CronExpression();
    }
}
