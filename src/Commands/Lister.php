<?php namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

use Daycry\CronJob\TaskRunner;

/**
 * Lists currently scheduled tasks.
 */
class Lister extends TaskCommand
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'cronjob:list';

	/**
	 * the Command's short description
	 *
	 * @var string
	 */
	protected $description = 'Lists the cronjobs currently set to run.';

	/**
	 * the Command's usage
	 *
	 * @var string
	 */
	protected $usage = 'cronjob:list';

	/**
	 * Lists upcoming tasks
	 *
	 * @param array $params
	 */
	public function run(array $params)
	{
        $this->getConfig();
		$settings = $this->getSettings();

		if( $settings->status !== 'enabled' )
		{
            CLI::newLine( 1 );
			CLI::write( '**** WARNING: Task running is currently disabled. ****', 'red' );
            CLI::newLine( 1 );
			CLI::write( '**** To re-enable tasks run: tasks:enable ****', 'black', 'green' );
            CLI::newLine( 1 );
		}

		$scheduler = \Config\Services::scheduler();

		$this->config->init( $scheduler );

		$runner = new TaskRunner();

		$tasks = [];

		foreach( $scheduler->getTasks() as $task )
		{
			$cron = service( 'cronExpression' );

			$nextRun = $cron->nextRun( $task->getExpression() );

			$tasks[] = [
				'name'     => $task->name ?: $task->getAction(),
				'type'     => $task->getType(),
				'next_run' => $nextRun,
				'runs_in'  => $nextRun->humanize(),
			];
		}

		usort( $tasks, function ( $a, $b )
        {
				return ( $a[ 'next_run' ] < $b[ 'next_run' ]) ? -1 : 1;
		});

		CLI::table( $tasks, 
            [
                'Name',
                'Type',
                'Next Run',
                '',
            ]
        );
	}
}