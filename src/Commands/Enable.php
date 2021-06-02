<?php namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Enables Task Running
 */
class Enable extends TaskCommand
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'cronjob:enable';

	/**
	 * the Command's short description
	 *
	 * @var string
	 */
	protected $description = 'Enables the cronjob runner.';

	/**
	 * the Command's usage
	 *
	 * @var string
	 */
	protected $usage = 'cronjob:enable';

	/**
	 * Enables task running
	 *
	 * @param array $params
	 */
	public function run(array $params)
	{
		$settings = $this->saveSettings( 'enabled' );

        if( $settings )
        {
            CLI::newLine( 1 );
			CLI::write( '**** CronJob is now Enabled. ****', 'white', 'red' );
			CLI::newLine( 1 );
        }else{
            CLI::newLine( 1 );
			CLI::error( '**** CronJob is already Enabled. ****' );
			CLI::newLine( 1 );
        }
	}
}