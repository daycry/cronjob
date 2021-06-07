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
	protected $testTime = null;

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

		$data = [];

		foreach( $tasks as $task )
		{
			$temp = [];
			if ( !$task->shouldRun( $this->testTime ) )
			{
				continue;
			}

			$error  = null;
			$start  = Time::now();
			$output = null;

			//var_dump( \strval( $start ) );exit;

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
				$end = Time::now();

				$log = new JobLog( ['task'     => $task, 'output'   => $output, 'runStart' => $start, 'runEnd'   => $end, 'error'    => $error ] );

				$temp = array(
					'name' => ( $task->name ) ? $task->name : null,
					'type' => $task->getType(),
					'action' => $task->getAction(),
					'environment' => \json_encode( $task->environments ),
					'output' => $output,
					'error' => $error,
					'start_at' => \strval( $start ),
					'end_at' => \strval( $end ),
					'duration' => $log->duration(),
					'test_time' => $this->testTime->format( 'Y-m-d H:i:s' ),

				);

				// Save performance info
				$this->performanceLogs[] = $temp;
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
		$this->testTime = new \DateTime( $time );

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
		$config = config( 'CronJob' );

		if( empty( $this->performanceLogs ) || !$config->saveLog )
		{
			return;
		}

		if( $config->logSavingMethod == 'database' )
		{
			$logModel = new \Daycry\CronJob\Models\CronJobLogModel();
			$logModel->setDBGroup( $config->databaseGroup );
			$logModel->setTableName( $config->tableName );
			$logModel->insertBatch( $this->performanceLogs );

		}else{

			// Ensure we have someplace to store the log
			if( file_exists( $config->FilePath . $config->FileName ) )
			{
				if( !is_dir( $config->FilePath ) ){ mkdir( $config->FilePath ); }
			}

			$fileName = 'jobs_' . date('Y-m-d--H-i-s') . '.json';

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
}