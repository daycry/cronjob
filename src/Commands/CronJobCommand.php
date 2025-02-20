<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTime;
use Daycry\CronJob\Config\CronJob as CronJobConfig;

/**
 * Base functionality for enable/disable.
 */
abstract class CronJobCommand extends BaseCommand
{
    /**
     * Command grouping.
     *
     * @var string
     */
    protected $group = 'Cronjob';

    protected CronJobConfig $config;

    /**
     * Get Config File
     */
    protected function getConfig()
    {
        $this->config = config('CronJob');
    }

    /**
     * Saves the settings.
     *
     * @param mixed $status
     */
    protected function saveSettings($status)
    {
        $this->getConfig();

        if (! file_exists($this->config->filePath . $this->config->fileName)) {
            $this->createDirectoryIfNotExists($this->config->filePath);

            $settings = $this->createSettingsArray($status);

            $this->writeSettingsToFile($settings);

            return $settings;
        }

        return false;
    }

    /**
     * Gets the settings, if they have never been
     * saved, save them.
     */
    protected function getSettings()
    {
        $this->getConfig();

        if (file_exists($this->config->filePath . $this->config->fileName)) {
            return json_decode(file_get_contents($this->config->filePath . $this->config->fileName));
        }

        return false;
    }

    protected function disabled()
    {
        $this->writeMessage('**** Cronjob is now disabled. ****', 'white', 'red');
    }

    protected function enabled()
    {
        $this->writeMessage('**** CronJob is now Enabled. ****', 'black', 'green');
    }

    protected function alreadyEnabled()
    {
        $this->writeMessage('**** CronJob is already Enabled. ****', 'error');
    }

    protected function tryToEnable()
    {
        $this->writeMessage('**** WARNING: Task running is currently disabled. ****', 'red');
        $this->writeMessage('**** To re-enable tasks run: cronjob:enable ****', 'black', 'green');
    }

    private function createDirectoryIfNotExists($path)
    {
        if (! is_dir($path)) {
            mkdir($path);
        }
    }

    private function createSettingsArray($status)
    {
        return [
            'status' => $status,
            'time'   => (new DateTime())->format('Y-m-d H:i:s'),
        ];
    }

    private function writeSettingsToFile($settings)
    {
        file_put_contents(
            $this->config->filePath . $this->config->fileName,
            json_encode($settings, JSON_PRETTY_PRINT),
        );
    }

    private function writeMessage($message, $foreground = null, $background = null)
    {
        CLI::newLine(1);
        if ($foreground === 'error') {
            CLI::error($message);
        } else {
            CLI::write($message, $foreground, $background);
        }
        CLI::newLine(1);
    }
}
