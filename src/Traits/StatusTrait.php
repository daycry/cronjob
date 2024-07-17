<?php

namespace Daycry\CronJob\Traits;

use Daycry\CronJob\Job;

/**
 * Trait StatusTrait
 *
 * @mixin Job
 * @package Daycry\CronJob
 */
trait StatusTrait
{
    protected function isRunningFlagPath(): string
    {
        return setting('CronJob.filePath') . 'running/' . $this->getName();
    }

    protected function createConfigFolderIfNeeded(): void
    {
        $folder = setting('CronJob.filePath');

        if (is_dir($folder)) {
            return;
        }

        mkdir($folder);
    }

    protected function createTasksRunningFolderIfNeeded(): void
    {
        $folder = setting('CronJob.filePath') . 'running';

        if (is_dir($folder)) {
            return;
        }

        mkdir($folder);
    }

    protected function createFoldersIfNeeded(): void
    {
        $this->createConfigFolderIfNeeded();
        $this->createTasksRunningFolderIfNeeded();
    }

    /**
     * Saves the running flag.
     */
    public function saveRunningFlag($flag)
    {
        $name = $this->getName();

        $path = $this->isRunningFlagPath();

        if ($flag) {
            if (file_exists($path)) {
                return false;
            }

            $this->createFoldersIfNeeded();

            $data = [
                "flag" => $flag,
                "time" => (new \DateTime())->format('Y-m-d H:i:s')
            ];

            // write the file with json content
            file_put_contents(
                $path,
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                )
            );

            return $data;
        } else {
            @unlink(setting('CronJob.filePath') . '/running/' . $name);
        }

        return false;
    }

    /**
     * get cronjob status
     */
    public function status(): bool
    {
        $config = config('CronJob');

        $name = ($this->name) ? $this->name : $this->buildName();

        if (!is_dir(setting('CronJob.filePath')) || !is_dir(setting('CronJob.filePath') . 'disable/') || !file_exists(setting('CronJob.filePath') . 'disable/' . $name)) {
            return true;
        }
        return false;
    }

    /**
     * disable cronjob
     */
    public function disable(): bool
    {
        $config = config('CronJob');

        $this->name = ($this->name) ? $this->name : $this->buildName();

        if (!file_exists(setting('CronJob.filePath') . 'disable/' . $this->name)) {
            // dir doesn't exist, make it
            if (!is_dir(setting('CronJob.filePath'))) {
                mkdir(setting('CronJob.filePath'));
            }

            if (!is_dir(setting('CronJob.filePath') . 'disable/')) {
                mkdir(setting('CronJob.filePath') . 'disable/');
            }

            $data = [
                "name" => $this->name,
                "time" => (new \DateTime())->format('Y-m-d H:i:s')
            ];

            // write the file with json content
            file_put_contents(
                setting('CronJob.filePath') . '/disable/' . $this->name,
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                )
            );

            return true;
        }
        return false;
    }

    /**
     * enable cronjob
     */
    public function enable(): bool
    {
        $config = config('CronJob');

        $this->name = ($this->name) ? $this->name : $this->buildName();


        if (file_exists(setting('CronJob.filePath') . 'disable/' . $this->name)) {
            @unlink(setting('CronJob.filePath') . '/disable/' . $this->name);
            return true;
        }

        return false;
    }

    /**
     * @return ?array{
     *     name: string,
     *     time: string,
     * }
     */
    public function getIsRunningInfo(): ?array
    {
        $path = $this->isRunningFlagPath();

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return json_decode($content, true);
    }
}
