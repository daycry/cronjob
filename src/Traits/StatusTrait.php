<?php

namespace Daycry\CronJob\Traits;

/**
 * Trait StatusTrait
 *
 * @package Daycry\CronJob
 */
trait StatusTrait
{
    /**
     * Saves the running flag.
     */
    public function saveRunningFlag($flag)
    {
        $config = config('CronJob');

        $name = ($this->name) ? $this->name : $this->buildName();
        if ($flag) {
            if (!file_exists(setting('CronJob.filePath') . 'running/' . $name)) {
                // dir doesn't exist, make it
                if (!is_dir(setting('CronJob.filePath'))) {
                    mkdir(setting('CronJob.filePath'));
                }

                if (!is_dir(setting('CronJob.filePath') . 'running/')) {
                    mkdir(setting('CronJob.filePath') . 'running/');
                }

                $data = [
                    "flag" => $flag,
                    "time" => (new \DateTime())->format('Y-m-d H:i:s')
                ];

                // write the file with json content
                file_put_contents(
                    setting('CronJob.filePath') . '/running/' . $name,
                    json_encode(
                        $data,
                        JSON_PRETTY_PRINT
                    )
                );

                return $data;
            }
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
}
