<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\CLI;
use Daycry\CronJob\Config\CronJob;
use Daycry\CronJob\Config\Services;
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
    protected $options = ['-testTime' => 'Set Date to run script', '-only' => 'Set name of jobs that you want run separated with comma'];

    /**
     * Runs tasks at the proper time.
     */
    public function run(array $params)
    {
        $this->getConfig();
        $settings = $this->getSettings();

        if (! $settings || (isset($settings->status) && $settings->status !== 'enabled')) {
            $this->tryToEnable();

            return false;
        }

        CLI::newLine(1);
        CLI::write('**** Running Tasks... ****', 'white', 'red');
        CLI::newLine(1);

        /** @var CronJob $config */
        $config = config('CronJob');
        $config->init(Services::scheduler());

        $runner = new JobRunner();

        $testTime = $params['testTime'] ?? CLI::getOption('testTime');

        if ($testTime) {
            $runner->withTestTime($testTime);
        }

        $only = $params['only'] ?? CLI::getOption('only');

        if ($only) {
            $only = explode(',', $only);
            $runner->only($only);
        }

        $runner->run();

        CLI::newLine(1);
        CLI::write('**** Completed ****', 'white', 'red');
        CLI::newLine(1);
    }
}
