<?php

namespace Daycry\CronJob\Commands;

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
    protected $usage = 'cronjob:run [options]';

    /**
     * the Command's option
     *
     * @var array
     */
    protected $options = [ '-testTime' => 'Set Date to run script' ];

    /**
     * Runs tasks at the proper time.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $this->getConfig();
        $settings = $this->getSettings();

        if (!$settings || (isset($settings->status) && $settings->status !== 'enabled')) {
            $this->tryToEnable();
            return false;
        }

        CLI::newLine(1);
        CLI::write('**** Running Tasks... ****', 'white', 'red');
        CLI::newLine(1);

        $this->config->init(\Config\Services::scheduler());

        $runner = new JobRunner();

        $testTime = $params[ 'testTime' ] ?? CLI::getOption('testTime');

        if ($testTime) {
            $runner->withTestTime($testTime);
        }
        $runner->run();

        CLI::newLine(1);
        CLI::write('**** Completed ****', 'white', 'red');
        CLI::newLine(1);
    }
}
