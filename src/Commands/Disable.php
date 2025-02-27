<?php

namespace Daycry\CronJob\Commands;

/**
 * Disable Task Running.
 */
class Disable extends CronJobCommand
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
     */
    public function run(array $params)
    {
        $this->getConfig();

        // delete the file with json content
        @unlink($this->config->filePath . $this->config->fileName);

        $this->disabled();
    }
}
