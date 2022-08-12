<?php

namespace Daycry\CronJob\Config;

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
}
