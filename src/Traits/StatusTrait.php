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
        if (! is_dir($folder)) {
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
     *
     * @return array|false
     */
    public function saveRunningFlag($flag)
    {
        $path = $this->isRunningFlagPath();
        $lockDir = rtrim($this->config->lockPath ?? ($this->config->filePath . 'locks/'), '/\\') . DIRECTORY_SEPARATOR;
        if (! is_dir($lockDir)) {
            mkdir($lockDir, 0777, true);
        }
        $lockFile = $lockDir . md5($this->getName()) . '.lock';

        if ($flag) {
            $fh = @fopen($lockFile, 'c+');
            if (! $fh) {
                return false;
            }
            // Obtain exclusive non-blocking lock
            if (! @flock($fh, LOCK_EX | LOCK_NB)) {
                // Read existing metadata to decide if we can reclaim
                $raw = @file_get_contents($lockFile);
                $meta = @json_decode($raw ?? '', true) ?: [];
                $ttl = $this->config->lockTTL;
                $mtime = @filemtime($lockFile) ?: 0;
                $age = time() - $mtime;
                $pid = $meta['pid'] ?? null;
                $heartbeat = isset($meta['heartbeat']) ? strtotime($meta['heartbeat']) : null;
                $hbStale = $heartbeat ? (time() - $heartbeat) > ($ttl ?? 0) : false;
                $pidDead = $pid && PHP_OS_FAMILY !== 'Windows' && function_exists('posix_kill') ? ! @posix_kill((int) $pid, 0) : false;
                $expired = ($ttl !== null && $age > $ttl) || $pidDead || $hbStale;
                if ($expired && @flock($fh, LOCK_EX | LOCK_NB)) {
                    ftruncate($fh, 0);
                    $data = $this->buildLockMetadata($flag, true);
                    fwrite($fh, json_encode($data, JSON_PRETTY_PRINT));
                    fflush($fh);
                    $this->lockHandle = $fh;
                    return $data;
                }
                fclose($fh);
                return false; // Still locked and not stale
            }
            // Fresh lock acquired
            ftruncate($fh, 0);
            $data = $this->buildLockMetadata($flag, false);
            fwrite($fh, json_encode($data, JSON_PRETTY_PRINT));
            fflush($fh);
            $this->lockHandle = $fh;
            return $data;
        }

        // Release
        if (isset($this->lockHandle) && is_resource($this->lockHandle)) {
            @flock($this->lockHandle, LOCK_UN);
            @fclose($this->lockHandle);
            unset($this->lockHandle);
        }
        @unlink($lockFile);
        @unlink($path); // legacy path cleanup
        return false;
    }

    /**
     * Get cronjob status
     */
    public function status(): bool
    {
        $name = $this->getName();

        return ! is_dir($this->config->filePath) || ! is_dir($this->config->filePath . 'disable/') || ! file_exists($this->config->filePath . 'disable/' . $name);
    }

    /**
     * Disable cronjob
     */
    public function disable(): bool
    {
        $name        = $this->getName();
        $disablePath = $this->config->filePath . 'disable/' . $name;

        if (! file_exists($disablePath)) {
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
        $name        = $this->getName();
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
     * @deprecated Legacy support for background command completion (used by cronjob:finish). Will be removed in a future major version; prefer lock metadata inspection.
     */
    public function getIsRunningInfo(): ?array
    {
        $path = $this->isRunningFlagPath();

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return json_decode($content, true);
    }

    private function getName(): string
    {
        return $this->name ?: $this->buildName();
    }

    /**
     * Build lock metadata array.
     *
     * @param mixed $flag
     * @return array<string,mixed>
     */
    private function buildLockMetadata($flag, bool $stolen): array
    {
        $now = new DateTime();
        $data = [
            'flag'      => $flag,
            'time'      => $now->format('Y-m-d H:i:s'),
            'pid'       => getmypid(),
            'heartbeat' => $now->format('c'),
            'job'       => $this->getName(),
        ];
        if ($stolen) {
            $data['stolen'] = true;
        }
        return $data;
    }
}
