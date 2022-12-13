<?php

namespace Daycry\CronJob\Commands;

use Config\Autoload;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Enables Task Running
 */
class Assets extends CronJobCommand
{
    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'cronjob:assets';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Publish the assets.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'cronjob:assets';

    /**
     * Assets Path
     *
     * @var string
     */
    protected $assetsPath = '';

    /**
     * Enables task running
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $this->determineSourcePath();

        $this->publishAssets();
    }

    protected function publishAssets()
    {
        helper('filesystem');
        directory_mirror($this->assetsPath, FCPATH . 'vendor' . DIRECTORY_SEPARATOR . 'cronjob', true);
    }

    /**
     * Determines the current source path from which all other files are located.
     */
    protected function determineSourcePath()
    {
        $this->assetsPath = realpath(__DIR__ . '/../../public/');

        if ($this->assetsPath == '/' || empty($this->assetsPath)) {
            CLI::error('Unable to determine the correct source directory. Bailing.');
            exit();
        }
    }
}
