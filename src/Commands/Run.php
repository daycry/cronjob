<?php namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

use Daycry\CronJob\JobRunner;

/**
 * Runs current tasks.
 */
class Run extends CronJobCommand
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'cronjob:run';

	/**
	 * the Command's short description
	 *
	 * @var string
	 */
	protected $description = 'Runs tasks based on the schedule, should be configured as a crontask to run every minute.';

	/**
	 * the Command's usage
	 *
	 * @var string
	 */
	protected $usage = 'cronjob:run';

	/**
	 * Runs tasks at the proper time.
	 *
	 * @param array $params
	 */
	public function run( array $params )
	{
        $this->getConfig();
		$settings = $this->getSettings();

		if( !$settings || ( isset( $settings->status ) && $settings->status !== 'enabled' ) )
		{
			$this->tryToEnable();
			return false;
		}

        CLI::newLine( 1 );
		CLI::write( '**** Running Tasks... ****', 'white', 'red' );
        CLI::newLine( 1 );

		$this->config->init( \Config\Services::scheduler() );

		$runner = new JobRunner();

		$runner->run();

        CLI::newLine( 1 );
		CLI::write( '**** Completed ****', 'white', 'red' );
        CLI::newLine( 1 );
	}
}