<?php namespace Daycry\CronJob;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Config\Database;

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
     * Stores aliases of tasks to run
     * If empty, All tasks will be executed as per their schedule
     *
     * @var array
     */
    protected $only = [];

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
			// If specific tasks were chosen then skip executing remaining tasks
            if( !empty( $this->only ) && ! in_array( $task->name, $this->only, true ) )
			{
                continue;
            }

			if ( !$task->shouldRun( $this->testTime ) && empty( $this->only ) )
			{
				continue;
			}

			$error  = null;
			$start  = Time::now();
			$output = null;

			$this->cliWrite( 'Processing: ' . ( $task->name ?: 'Task' ), 'green' );

			try
			{
				$output = $task->run();
				
				if( !$output )
				{
					$output = \ob_get_contents();
				}

				$this->cliWrite( 'Executed: ' . ( $task->name ?: 'Task' ), 'cyan' );
			}
			catch( \Throwable $e )
			{
				$this->cliWrite( 'Failed: ' . ($task->name ?: 'Task'), 'red' );
				log_message( 'error', $e->getMessage(), $e->getTrace() );
				$error = $e;
			}
			finally
			{
				$end = Time::now();

				$jobLog = new JobLog( [ 'task' => $task, 'output' => $output, 'runStart' => $start, 'runEnd' => $end, 'error' => $error, 'testTime' => $this->testTime ] );
				//$this->performanceLogs[] = $jobLog;
				
				$this->performanceLogs[] = array(
					'name' => ( $task->name ) ? $task->name : null,
					'type' => $task->getType(),
					'action' => ( \is_object( $task->getAction() ) ) ? \json_encode( $task->getAction() ) : $task->getAction(),
					'environment' => \json_encode( $task->environments ),
					'output' => $output,
					'error' => $error,
					'start_at' => \strval( $start ),
					'end_at' => \strval( $end ),
					'duration' => $jobLog->duration(),
					'test_time' => ($this->testTime) ? $this->testTime->format( 'Y-m-d H:i:s' ) : null
				);

				$this->storePerformanceLogs();
			}
		}
	}

	/**
     * Specify tasks to run
     *
     * @param array $tasks
     *
     * @return TaskRunner
     */
    public function only( Array $tasks = [] ) : JobRunner
    {
        $this->only = $tasks;

        return $this;
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
	public function withTestTime( String $time ) : JobRunner
	{
		$this->testTime = new \DateTime( $time );

		return $this;
	}


	/**
	 * Performance log information is stored
	 * at /writable/tasks/tasks_yyyy_mm_dd.json
	 */
	protected function storePerformanceLogs()
	{
		$config = config( 'CronJob' );

		if( empty( $this->performanceLogs ) )
		{
			return;
		}

		if( $config->logSavingMethod == 'database' )
		{
			$db = Database::connect( $config->databaseGroup );
			$logModel = new \Daycry\CronJob\Models\CronJobLogModel( $db );
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

	/**
     * Write a line to command line interface
     *
     * @param string      $text
     * @param string|null $foreground
     */
    protected function cliWrite( String $text, String $foreground = null )
    {
        // Skip writing to cli in tests
        if( defined( "ENVIRONMENT" ) && ENVIRONMENT === "testing" )
		{
            return ;
        }

        if( !is_cli() )
		{
            return ;
        }

        CLI::write("[" . date("Y-m-d H:i:s") . "] " . $text, $foreground );
    }
}