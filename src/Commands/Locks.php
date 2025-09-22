<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\CLI;
use Daycry\CronJob\Config\CronJob as CronJobConfig;
use Daycry\CronJob\Config\Services;
use DateTimeImmutable;

/**
 * List active CronJob locks with metadata.
 */
class Locks extends CronJobCommand
{
    /** @var string */
    protected $name = 'cronjob:locks';
    /** @var string */
    protected $description = 'Lista los locks activos de cronjob con metadatos.';
    /** @var string */
    protected $usage = 'cronjob:locks';

    /**
     * @param array<int,string> $params
     * @return mixed
     */
    public function run(array $params)
    {
        $this->getConfig();
        $force  = in_array('--force', $params, true);
        $silent = in_array('--silent', $params, true) ? true : false; // explicit bool for static analysis
        $json   = in_array('--json', $params, true);
        if ($json) { $silent = true; } // JSON implies no human formatting
        $settings = $this->getSettings();
        if (! $force && (! $settings || (isset($settings->status) && $settings->status !== 'enabled'))) {
            $this->tryToEnable();
            return CLI::error('CronJob system not enabled.');
        }

        /** @var CronJobConfig $config */
        $config = config('CronJob');
        $lockPath = rtrim($config->lockPath, '/\\') . DIRECTORY_SEPARATOR;
        if (! is_dir($lockPath)) {
            $msg = 'No lock directory found: ' . $lockPath;
            if ($json) { return ['message' => $msg, 'locks' => []]; }
            if (! $silent) { CLI::write($msg, 'yellow'); }
            return $msg;
        }

        $files = glob($lockPath . '*.lock') ?: [];
        if (empty($files)) {
            $msg = 'No active locks.';
            if ($json) { return ['message' => $msg, 'locks' => []]; }
            if (! $silent) { CLI::write($msg, 'green'); }
            return $msg;
        }

        $rows = [];
        $now = time();
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            $meta = @json_decode($raw ?: '', true) ?: [];
            $mtime = @filemtime($file) ?: 0;
            $age = $now - $mtime;
            $rows[] = [
                'job' => $meta['job'] ?? '-',
                'file' => basename($file),
                'pid' => $meta['pid'] ?? '-',
                'stolen' => isset($meta['stolen']) ? 'yes' : 'no',
                'age_s' => $age,
                'heartbeat' => $meta['heartbeat'] ?? '-',
                'time' => $meta['time'] ?? '-',
            ];
        }

        usort($rows, static fn($a, $b) => $a['age_s'] <=> $b['age_s']);
        if (! $json && ! $silent) {
            CLI::table($rows, ['Job','File','PID','Stolen','Age(s)','Heartbeat','Acquired']);
        }

        // TTL hint
        if ($config->lockTTL !== null && ! $silent && ! $json) {
            CLI::write('TTL configurado: ' . $config->lockTTL . 's', 'yellow');
        }
        if ($json) { return ['locks' => $rows, 'count' => count($rows)]; }
        return $rows; // allow tests to assert structure
    }
}
