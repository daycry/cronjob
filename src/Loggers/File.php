<?php

namespace Daycry\CronJob\Loggers;

use CodeIgniter\I18n\Time;
use Daycry\CronJob\Config\CronJob as CronJobConfig;
use Daycry\CronJob\Interfaces\LoggerInterface;

class File implements LoggerInterface
{
    public function save(array $data): void
    {
        /** @var CronJobConfig config */
        $config   = config('CronJob');
        $path     = $config->filePath . $data['name'];
        $fileName = $path . '/' . $config->fileName . '.json';

        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if (file_exists($fileName)) {
            $logs = \json_decode(\file_get_contents($fileName));
        } else {
            $logs = [];
        }

        // Make sure we have room for one more
        if ((is_countable($logs) ? count($logs) : 0) >= $config->maxLogsPerJob) {
            array_pop($logs);
        }

        // Add the log to the top of the array
        array_unshift($logs, $data);

        file_put_contents(
            $fileName,
            json_encode(
                $logs,
                JSON_PRETTY_PRINT,
            ),
        );
    }

    public function lastRun(string $name): string|Time
    {
        /** @var CronJobConfig config */
        $config   = config('CronJob');
        $path     = $config->filePath . $name;
        $fileName = $path . '/' . $config->fileName . '.json';

        if (! is_dir($path)) {
            return '--';
        }

        $logs = \json_decode(\file_get_contents($fileName));

        if (empty($logs)) {
            return '--';
        }

        return Time::parse($logs[0]->start_at);
    }

    public function getLogs(string $name): array
    {
        /** @var CronJobConfig config */
        $config   = config('CronJob');
        $path     = $config->filePath . $name;
        $fileName = $path . '/' . $config->fileName . '.json';

        if (is_dir($path) && is_file($fileName)) {
            return \json_decode(\file_get_contents($fileName));
        }

        return [];
    }
}
