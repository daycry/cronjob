<?php namespace Daycry\CronJob;

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
	public function cron( string $expression )
	{
		if( !\Cron\CronExpression::isValidExpression( $expression ) )
		{
			throw CronJobException::forInvalidExpression();
		}

		$this->expression = \Cron\CronExpression::factory( $expression )->getExpression();

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
		if( !empty( $time ) )
		{
			[ $min, $hour ] = $this->parseTime( $time );
		}

		$cron = \Cron\CronExpression::factory( '@daily' );

		$cron->setPart( 0, $min );
		$cron->setPart( 1, $hour );

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
		$cron = \Cron\CronExpression::factory( '@hourly' );

		if( !is_null( $minute ) ){ $cron->setPart( 0, $minute ); }

		$this->expression = $cron->getExpression();

		return $this;
	}


	/**
	 * Runs at every hour or every x hours
	 *
	 * @param int  $hour
	 * @param null $minute
	 * @return self
	 */
	public function everyHour( int $hour = 1, $minute = null )
	{
		$cron = \Cron\CronExpression::factory( '@hourly' );

		if( !is_null( $hour ) ){ $cron->setPart( 1, '*/' . $hour ); }
		if( !is_null( $minute ) ){ $cron->setPart( 0, $minute ); }

		$this->expression = $cron->getExpression();

		return $this;
	}

	/**
	 * Runs in a specific range of hours
	 *
	 * @param int $fromHour
	 * @param int $toHour
	 * @return self
	 */
	public function betweenHours( int $fromHour, int $toHour )
	{
		$cron = \Cron\CronExpression::factory( '@hourly' );
		$cron->setPart( 1, implode( ",", $fromHour . "-" . $toHour ) );
		
		$this->expression = $cron->getExpression();

		return $this;
	}

	/**
	 * Runs on a specific choosen hours
	 *
	 * @param array $hours
	 * @return self
	 */
	public function hours( array $hours )
	{
		$cron = \Cron\CronExpression::factory( '@hourly' );

		$cron->setPart( 1, implode( ",", $hours ) );

		$this->expression = $cron->getExpression();

		return $this;
	}

	/**
	 * Set the execution time to every minute or every x minutes.
	 *
	 * @param int|string|null When set, specifies that the job will be run every $minute minutes
	 *
	 * @return self
	 */
	public function everyMinute( $minute = null )
	{
		$minute = is_null( $minute ) ? "*" : '*/' . $minute;

		$cron = \Cron\CronExpression::factory( $minute . ' * * * *' );

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
		return $this->everyMinute( 5 );
	}

	/**
	 * Runs every 15 minutes
	 *
	 * @return $this
	 */
	public function everyFifteenMinutes()
	{
		return $this->everyMinute( 15 );
	}

	/**
	 * Runs every 30 minutes
	 *
	 * @return $this
	 */
	public function everyThirtyMinutes()
	{
		return $this->everyMinute( 30 );
	}


	/**
	 * Runs in a specific range of minutes
	 *
	 * @param int $fromMinute
	 * @param int $toMinute
	 * @return self
	 */
	public function betweenMinutes( int $fromMinute, int $toMinute )
	{
		$cron = \Cron\CronExpression::factory( '@hourly' );

		$cron->setPart( 0, $fromMinute . "-" . $toMinute );
		
		$this->expression = $cron->getExpression();

		return $this;
	}

	/**
	 * Runs on a specific choosen minutes
	 *
	 * @param array $minutes
	 * @return self
	 */
	public function minutes( array $minutes )
	{
		$cron = \Cron\CronExpression::factory( '@hourly' );

		$cron->setPart( 0, implode( ",", $minutes ) );

		$this->expression = $cron->getExpression();

		return $this;
	}


	/**
	 * Runs on specific days
	 *
	 * @param array|int $days [0 : Sunday - 6 : Saturday]
	 * @return self
	 */
	public function days($days)
	{
		if (!is_array($days))
		{
			$days = [$days];
		}

		$this->expression['dayOfWeek'] = implode(",", $days);

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

		if (! empty($time))
		{
			[$min, $hour] = $this->parseTime($time);
		}

		$this->expression['min']        = $min;
		$this->expression['hour']       = $hour;
		$this->expression['dayOfMonth'] = 1;

		return $this;
	}

	/**
	 * Runs on specific days of the month
	 *
	 * @param array|int $days [1-31]
	 * @return self
	 */
	public function daysOfMonth($days)
	{
		if (!is_array($days))
		{
			$days = [$days];
		}

		$this->expression['dayOfMonth'] = implode(",", $days);

		return $this;
	}

	/**
	 * Runs on specific months
	 *
	 * @param array $months
	 * @return self
	 */
	public function months(array $months = [])
	{
		$this->expression['month'] = implode(",", $months);
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

		if (! empty($time))
		{
			[$min, $hour] = $this->parseTime($time);
		}

		$this->expression['min']        = $min;
		$this->expression['hour']       = $hour;
		$this->expression['dayOfMonth'] = 1;
		$this->expression['month']      = '*/3';

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

		if (! empty($time))
		{
			[$min, $hour] = $this->parseTime($time);
		}

		$this->expression['min']        = $min;
		$this->expression['hour']       = $hour;
		$this->expression['dayOfMonth'] = 1;
		$this->expression['month']      = 1;

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

		if (! empty($time))
		{
			[$min, $hour] = $this->parseTime($time);
		}

		$this->expression['min']       = $min;
		$this->expression['hour']      = $hour;
		$this->expression['dayOfWeek'] = '1-5';

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

		if (! empty($time))
		{
			[$min, $hour] = $this->parseTime($time);
		}

		$this->expression['min']       = $min;
		$this->expression['hour']      = $hour;
		$this->expression['dayOfWeek'] = '6-7';

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

		if (! empty($time))
		{
			[$min, $hour] = $this->parseTime($time);
		}

		$this->expression['min']       = $min;
		$this->expression['hour']      = $hour;
		$this->expression['dayOfWeek'] = $day;

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
			date('G', $time),
		];
	}
}