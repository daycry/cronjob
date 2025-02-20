<?php

namespace Daycry\CronJob\Commands;

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
