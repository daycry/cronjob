<?php namespace Daycry\CronJob;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;

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
	protected $testTime;

	/**
	 * Stores execution logs for each
	 * task that was ran
	 *
	 * @var array
	 */
	protected $performanceLogs = [];

	public function __construct()
	{
		$this->scheduler = service( 'scheduler' );
	}

	/**
	 * The main entry point to run tasks within the system.
	 * Also handles collecting output and sending out
	 * notifications as necessary.
	 */
	public function run()
	{
		$tasks = $this->scheduler->getTasks();

		if( !count( $tasks ) )
		{
			return;
		}

		foreach( $tasks as $task )
		{
			$cron = \Cron\CronExpression::factory( $task->getExpression() );

			if( !$cron->isDue() )
			{
				continue;
			}

			$error  = null;
			$start  = Time::now();
			$output = null;

			try
			{
				$output = $task->run();
			}
			catch( \Throwable $e )
			{
				log_message( 'critical', $e->getMessage(), $e->getTrace() );
				$error = $e;
			}
			finally
			{
				// Save performance info
				$this->performanceLogs[] = ( new JobLog(
					[
						'task'     => $task,
						'output'   => $output,
						'runStart' => $start,
						'runEnd'   => Time::now(),
						'error'    => $error,
					]
				) )->getData();
			}
		}

		$this->storePerformanceLogs();
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
	public function withTestTime(string $time)
	{
		$this->testTime = $time;

		return $this;
	}

	/**
	 * Returns the performance logs, if any.
	 *
	 * @return array
	 */
	public function performanceLogs()
	{
		return $this->performanceLogs;
	}

	/**
	 * Performance log information is stored
	 * at /writable/tasks/tasks_yyyy_mm_dd.json
	 */
	protected function storePerformanceLogs()
	{
		if( empty( $this->performanceLogs ) )
		{
			return;
		}

		$config = config( 'CronJob' );

		// Ensure we have someplace to store the log
		if( file_exists( $config->FilePath . $config->FileName ) )
		{
			if( !is_dir( $config->FilePath ) ){ mkdir( $config->FilePath ); }
		}

		$fileName = 'jobs_' . date('Y_m_d') . '.json';

		// write the file with json content
		file_put_contents(
			$config->FilePath . $fileName,
			json_encode(
				$this->performanceLogs, 
				JSON_PRETTY_PRINT
			)
		);
	}
}