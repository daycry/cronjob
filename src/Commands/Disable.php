<?php namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Disable Task Running.
 */
class Disable extends TaskCommand
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'cronjob:disable';

	/**
	 * the Command's short description
	 *
	 * @var string
	 */
	protected $description = 'Disables the cronjob runner.';

	/**
	 * the Command's usage
	 *
	 * @var string
	 */
	protected $usage = 'cronjob:disable';

	/**
	 * Disables task running
	 *
	 * @param array $params
	 */
	public function run(array $params)
	{
        $this->getConfig();
        
		//delete the file with json content
		@unlink( $this->config->FilePath . $this->config->FileName );

		CLI::newLine( 1 );
		CLI::write( '**** Cronjob is now disabled. ****', 'black', 'green' );
		CLI::newLine( 1 );
	}
}