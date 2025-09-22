<?php

namespace Daycry\CronJob\Traits;

/**
 * Trait FrequenciesTrait
 *
 * Provides the methods to assign frequencies to individual tasks.
 */

use Cron\CronExpression;
use Daycry\CronJob\Exceptions\CronJobException;

trait FrequenciesTrait
{
    /**
     * The generated cron expression
     */
    protected string $expression = '* * * * *';

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
     * @return $this
     */
    public function cron(string $expression): self
    {
        if (! CronExpression::isValidExpression($expression)) {
            throw CronJobException::forInvalidExpression($expression);
        }

        $this->expression = (new CronExpression($expression))->getExpression();

        return $this;
    }

    /**
     * Runs daily at midnight, unless a time string is
     * passed in (like 4:08 pm)
     *
     * @return $this
     */
    public function daily(?string $time = null): self
    {
        // Defaults to 00:00 unless time provided
        return $this->applyParts([0 => '0', 1 => '0'], $time);
    }

    /**
     * Runs at the top of every hour or at a specific minute.
     *
     * @return $this
     */
    public function hourly(?int $minute = null): self
    {
        $minute = ($minute) ?: '0';
        return $this->applyParts([0 => (string) $minute, 1 => '*']);
    }

    /**
     * Runs at every hour or every x hours
     *
     * @return $this
     */
    public function everyHour(int $hour = 1, ?int $minute = null)
    {
        $minute = ($minute) ?: '0';
        $hour   = ($hour === 1) ? '*' : '*/' . $hour;
        return $this->applyParts([0 => (string) $minute, 1 => $hour]);
    }

    /**
     * Runs in a specific range of hours
     *
     * @return $this
     */
    public function betweenHours(int $fromHour, int $toHour)
    {
        return $this->applyParts([1 => $fromHour . '-' . $toHour]);
    }

    /**
     * Runs on a specific choosen hours
     *
     * @return $this
     */
    public function hours(array $hours)
    {
        if (! is_array($hours)) { // defensive (param already typed array)
            $hours = [$hours];
        }
        return $this->applyParts([1 => implode(',', $hours)]);
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
        $minute = null === $minute ? '*' : '*/' . $minute;
        return $this->applyParts([0 => $minute]);
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
     * @return $this
     */
    public function betweenMinutes(int $fromMinute, int $toMinute)
    {
        return $this->applyParts([0 => $fromMinute . '-' . $toMinute]);
    }

    /**
     * Runs on a specific choosen minutes
     *
     * @return $this
     */
    public function minutes(array $minutes)
    {
        if (! is_array($minutes)) { // defensive
            $minutes = [$minutes];
        }
        return $this->applyParts([0 => implode(',', $minutes)]);
    }

    /**
     * Runs on specific days
     *
     * @param array|int $days [0 : Sunday - 6 : Saturday]
     *
     * @return $this
     */
    public function days($days)
    {
        if (! is_array($days)) {
            $days = [$days];
        }
        return $this->applyParts([4 => implode(',', $days)]);
    }

    /**
     * Runs every Sunday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function sundays(?string $time = null)
    {
        return $this->setDayOfWeek(0, $time);
    }

    /**
     * Runs every monday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function mondays(?string $time = null)
    {
        return $this->setDayOfWeek(1, $time);
    }

    /**
     * Runs every Tuesday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function tuesdays(?string $time = null)
    {
        return $this->setDayOfWeek(2, $time);
    }

    /**
     * Runs every Wednesday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function wednesdays(?string $time = null)
    {
        return $this->setDayOfWeek(3, $time);
    }

    /**
     * Runs every Thursday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function thursdays(?string $time = null)
    {
        return $this->setDayOfWeek(4, $time);
    }

    /**
     * Runs every Friday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function fridays(?string $time = null)
    {
        return $this->setDayOfWeek(5, $time);
    }

    /**
     * Runs every Saturday at midnight, unless time passed in.
     *
     * @return $this
     */
    public function saturdays(?string $time = null)
    {
        return $this->setDayOfWeek(6, $time);
    }

    /**
     * Should run the first day of every month.
     *
     * @return $this
     */
    public function monthly(?string $time = null)
    {
        return $this->applyParts([0 => '0', 1 => '0', 2 => '1'], $time);
    }

    /**
     * Runs on specific days of the month
     *
     * @param array|int $days [1-31]
     *
     * @return $this
     */
    public function daysOfMonth($days)
    {
        if (! is_array($days)) {
            $days = [$days];
        }
        return $this->applyParts([2 => implode(',', $days)]);
    }

    /**
     * Runs on specific months
     *
     * @return $this
     */
    public function months(array $months = [])
    {
        return $this->applyParts([3 => implode(',', $months)]);
    }

    /**
     * Should run the first day of each quarter,
     * i.e. Jan 1, Apr 1, July 1, Oct 1
     *
     * @return $this
     */
    public function quarterly(?string $time = null)
    {
        return $this->applyParts([0 => '0', 1 => '0', 2 => '1', 3 => '*/3'], $time);
    }

    /**
     * Should run the first day of the year.
     *
     * @return $this
     */
    public function yearly(?string $time = null)
    {
        return $this->applyParts([0 => '0', 1 => '0', 2 => '1', 3 => '1'], $time);
    }

    /**
     * Should run M-F.
     *
     * @return $this
     */
    public function weekdays(?string $time = null)
    {
        return $this->applyParts([0 => '0', 1 => '0', 4 => '1-5'], $time);
    }

    /**
     * Should run Saturday and Sunday
     *
     * @return $this
     */
    public function weekends(?string $time = null)
    {
        return $this->applyParts([0 => '0', 1 => '0', 4 => '6-7'], $time);
    }

    /**
     * Internal function used by the everyMonday, etc functions.
     *
     * @return $this
     */
    protected function setDayOfWeek(int $day, ?string $time = null)
    {
        return $this->applyParts([0 => '0', 1 => '0', 4 => (string) $day], $time);
    }

    /**
     * Helper to mutate the current cron expression with optional time parsing.
     * $overrides is an associative array where the key is the cron field index (0-4)
     * and the value is the string to set. If a $time is provided it will always
     * override minute (0) and hour (1) unless those indexes are intentionally
     * provided with a different value in $overrides.
     */
    protected function applyParts(array $overrides, ?string $time = null): self
    {
        $cron = new CronExpression($this->expression);

        if (! empty($time)) {
            [$min, $hour]   = $this->parseTime($time); // [min, hour]
            $overrides[0] = $min;   // force parsed minute
            $overrides[1] = $hour;  // force parsed hour
        }

        foreach ($overrides as $index => $value) {
            if ($value === null) {
                continue;
            }
            $cron->setPart($index, $value);
        }

        $this->expression = $cron->getExpression();
        return $this;
    }

    /**
     * Parses a time string (like 4:08 pm) into mins and hours
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
