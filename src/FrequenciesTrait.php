<?php

namespace Daycry\CronJob;

/**
 * Trait FrequenciesTrait
 *
 * Provides the methods to assign frequencies to individual tasks.
 *
 * @package Daycry\CronJob
 */

use Daycry\CronJob\Exceptions\CronJobException;

trait FrequenciesTrait
{
    /**
     * If listed, will restrict this to running
     * within only those environments.
     *
     * @var null
     */
    protected $allowedEnvironments = null;

    /**
     * The generated cron expression
     *
     * @var string
     */
    protected $expression = '* * * * *';

    /**
     * Returns the generated expression.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Schedules the task through a raw crontab expression string.
     *
     * @param string $expression
     *
     * @return $this
     */
    public function cron(string $expression)
    {
        if (!\Cron\CronExpression::isValidExpression($expression)) {
            throw CronJobException::forInvalidExpression($expression);
        }

        $this->expression = (new \Cron\CronExpression($expression))->getExpression();

        return $this;
    }

    /**
     * Runs daily at midnight, unless a time string is
     * passed in (like 4:08 pm)
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function daily(string $time = null)
    {
        $min = $hour = 0;
        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron = new \Cron\CronExpression($this->expression);

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs at the top of every hour or at a specific minute.
     *
     * @return $this
     */
    public function hourly(int $minute = null)
    {
        $cron = new \Cron\CronExpression($this->expression);


        $minute = ($minute) ? $minute : '0';

        $cron->setPart(0, $minute);
        $cron->setPart(1, '*');

        $this->expression = $cron->getExpression();

        return $this;
    }


    /**
     * Runs at every hour or every x hours
     *
     * @param int $hour
     * @param int|null $minute
     * @return $this
     */
    public function everyHour(int $hour = 1, ?int $minute = null)
    {
        $cron = new \Cron\CronExpression($this->expression);

        $minute = ($minute) ? $minute : '0';
        $hour = ($hour === 1) ? '*' : '*/' . $hour;

        $cron->setPart(0, $minute);
        $cron->setPart(1, $hour);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs in a specific range of hours
     *
     * @param int $fromHour
     * @param int $toHour
     * @return $this
     */
    public function betweenHours(int $fromHour, int $toHour)
    {
        $cron = new \Cron\CronExpression($this->expression);
        $cron->setPart(1, $fromHour . "-" . $toHour);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs on a specific choosen hours
     *
     * @param array $hours
     * @return $this
     */
    public function hours(array $hours)
    {
        $cron = new \Cron\CronExpression($this->expression);

        if (!is_array($hours)) {
            $hours = [$hours];
        }

        $cron->setPart(1, implode(",", $hours));

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Set the execution time to every minute or every x minutes.
     *
     * @param int|string|null When set, specifies that the job will be run every $minute minutes
     *
     * @return $this
     */
    public function everyMinute(?int $minute = null)
    {
        $minute = is_null($minute) ? "*" : '*/' . $minute;

        $cron = new \Cron\CronExpression($this->expression);
        $cron->setPart(0, $minute);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs every 5 minutes
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->everyMinute(5);
    }

    /**
     * Runs every 15 minutes
     *
     * @return $this
     */
    public function everyFifteenMinutes()
    {
        return $this->everyMinute(15);
    }

    /**
     * Runs every 30 minutes
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->everyMinute(30);
    }


    /**
     * Runs in a specific range of minutes
     *
     * @param int $fromMinute
     * @param int $toMinute
     * @return $this
     */
    public function betweenMinutes(int $fromMinute, int $toMinute)
    {
        $cron = new \Cron\CronExpression($this->expression);

        $cron->setPart(0, $fromMinute . "-" . $toMinute);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs on a specific choosen minutes
     *
     * @param array $minutes
     * @return $this
     */
    public function minutes(array $minutes)
    {
        $cron = new \Cron\CronExpression($this->expression);

        if (!is_array($minutes)) {
            $minutes = [ $minutes ];
        }

        $cron->setPart(0, implode(",", $minutes));

        $this->expression = $cron->getExpression();

        return $this;
    }


    /**
     * Runs on specific days
     *
     * @param array|int $days [0 : Sunday - 6 : Saturday]
     * @return $this
     */
    public function days($days)
    {
        $cron = new \Cron\CronExpression($this->expression);

        if (!is_array($days)) {
            $days = [ $days ];
        }

        $cron->setPart(4, implode(",", $days));

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs every Sunday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function sundays(string $time = null)
    {
        return $this->setDayOfWeek(0, $time);
    }

    /**
     * Runs every monday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function mondays(string $time = null)
    {
        return $this->setDayOfWeek(1, $time);
    }

    /**
     * Runs every Tuesday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function tuesdays(string $time = null)
    {
        return $this->setDayOfWeek(2, $time);
    }

    /**
     * Runs every Wednesday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function wednesdays(string $time = null)
    {
        return $this->setDayOfWeek(3, $time);
    }

    /**
     * Runs every Thursday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function thursdays(string $time = null)
    {
        return $this->setDayOfWeek(4, $time);
    }

    /**
     * Runs every Friday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function fridays(string $time = null)
    {
        return $this->setDayOfWeek(5, $time);
    }

    /**
     * Runs every Saturday at midnight, unless time passed in.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function saturdays(string $time = null)
    {
        return $this->setDayOfWeek(6, $time);
    }

    /**
     * Should run the first day of every month.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function monthly(string $time = null)
    {
        $min = $hour = 0;

        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron = new \Cron\CronExpression($this->expression);

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);
        $cron->setPart(2, 1);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs on specific days of the month
     *
     * @param array|int $days [1-31]
     * @return $this
     */
    public function daysOfMonth($days)
    {
        $cron = new \Cron\CronExpression($this->expression);

        if (!is_array($days)) {
            $days = [ $days ];
        }

        $cron->setPart(2, implode(",", $days));

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Runs on specific months
     *
     * @param array $months
     * @return $this
     */
    public function months(array $months = [])
    {
        $cron = new \Cron\CronExpression($this->expression);

        $cron->setPart(3, implode(",", $months));

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Should run the first day of each quarter,
     * i.e. Jan 1, Apr 1, July 1, Oct 1
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function quarterly(string $time = null)
    {
        $min = $hour = 0;

        $cron = new \Cron\CronExpression($this->expression);

        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);
        $cron->setPart(2, 1);
        $cron->setPart(3, '*/3');

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Should run the first day of the year.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function yearly(string $time = null)
    {
        $min = $hour = 0;

        $cron = new \Cron\CronExpression($this->expression);

        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);
        $cron->setPart(2, 1);
        $cron->setPart(3, 1);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Should run M-F.
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function weekdays(string $time = null)
    {
        $min = $hour = 0;

        $cron = new \Cron\CronExpression($this->expression);

        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);
        $cron->setPart(4, '1-5');

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Should run Saturday and Sunday
     *
     * @param string|null $time
     *
     * @return $this
     */
    public function weekends(string $time = null)
    {
        $min = $hour = 0;

        $cron = new \Cron\CronExpression($this->expression);

        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);
        $cron->setPart(4, '6-7');

        $this->expression = $cron->getExpression();

        return $this;
    }


    /**
     * Internal function used by the everyMonday, etc functions.
     *
     * @param integer     $day
     * @param string|null $time
     *
     * @return $this
     */
    protected function setDayOfWeek(int $day, string $time = null)
    {
        $min = $hour = '*';

        $cron = new \Cron\CronExpression($this->expression);

        if (!empty($time)) {
            [ $min, $hour ] = $this->parseTime($time);
        }

        $cron->setPart(0, $min);
        $cron->setPart(1, $hour);
        $cron->setPart(4, $day);

        $this->expression = $cron->getExpression();

        return $this;
    }

    /**
     * Parses a time string (like 4:08 pm) into mins and hours
     *
     * @param string $time
     */
    protected function parseTime(string $time)
    {
        $time = strtotime($time);

        return [
            date('i', $time), // mins
            date('H', $time),
        ];
    }
}
