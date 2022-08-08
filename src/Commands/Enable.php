<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Enables Task Running
 */
class Enable extends CronJobCommand
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
        $settings = $this->saveSettings('enabled');

        if ($settings) {
            $this->enabled();
        } else {
            $this->alreadyEnabled();
        }
    }
}
