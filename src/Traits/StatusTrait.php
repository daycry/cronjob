<?php

namespace Daycry\CronJob\Traits;

use DateTime;
use Daycry\CronJob\Job;

/**
 * Trait StatusTrait
 *
 * @mixin Job
 */
trait StatusTrait
{
    protected function isRunningFlagPath(): string
    {
        return $this->config->filePath . 'running/' . $this->getName();
    }

    protected function createFolderIfNotExists(string $folder): void
    {
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
    }

    protected function createFoldersIfNeeded(): void
    {
        $this->createFolderIfNotExists($this->config->filePath);
        $this->createFolderIfNotExists($this->config->filePath . 'running');
    }

    /**
     * Saves the running flag.
     *
     * @param mixed $flag
     * @return array|false
     */
    public function saveRunningFlag($flag)
    {
        $path = $this->isRunningFlagPath();

        if ($flag) {
            if (file_exists($path)) {
                return false;
            }

            $this->createFoldersIfNeeded();

            $data = [
                'flag' => $flag,
                'time' => (new DateTime())->format('Y-m-d H:i:s'),
            ];

            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

            return $data;
        }

        @unlink($path);

        return false;
    }

    /**
     * Get cronjob status
     */
    public function status(): bool
    {
        $name = $this->getName();

        return !is_dir($this->config->filePath) || !is_dir($this->config->filePath . 'disable/') || !file_exists($this->config->filePath . 'disable/' . $name);
    }

    /**
     * Disable cronjob
     */
    public function disable(): bool
    {
        $name = $this->getName();
        $disablePath = $this->config->filePath . 'disable/' . $name;

        if (!file_exists($disablePath)) {
            $this->createFolderIfNotExists($this->config->filePath . 'disable');

            $data = [
                'name' => $name,
                'time' => (new DateTime())->format('Y-m-d H:i:s'),
            ];

            file_put_contents($disablePath, json_encode($data, JSON_PRETTY_PRINT));

            return true;
        }

        return false;
    }

    /**
     * Enable cronjob
     */
    public function enable(): bool
    {
        $name = $this->getName();
        $disablePath = $this->config->filePath . 'disable/' . $name;

        if (file_exists($disablePath)) {
            @unlink($disablePath);

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

    private function getName(): string
    {
        return $this->name ?: $this->buildName();
    }
}
