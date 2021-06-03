<?php namespace Daycry\CronJob\Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\CronJob\Scheduler;

class CronJob extends BaseConfig
{
    public $FilePath = WRITEPATH . 'cronJob/';
    public $FileName = 'jobs';

    /**
	 * Register any tasks within this method for the application.
	 * Called by the TaskRunner.
	 *
	 * @param Scheduler $schedule
	 */
	public function init( Scheduler $schedule )
	{
		// $schedule->command('foo:bar')->nightly();

		// $schedule->shell('cp foo bar')->daily()->at('11:00 pm');

		// $schedule->call(function() { do something.... })->everyMonday()->named('foo')
	}
}