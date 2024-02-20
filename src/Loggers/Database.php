<?php

namespace Daycry\CronJob\Loggers;

use CodeIgniter\I18n\Time;
use Daycry\CronJob\Interfaces\LoggerInterface;
use Daycry\CronJob\Models\CronJobLogModel;

class Database implements LoggerInterface
{
    public function save(array $data): void
    {
        $logModel = new CronJobLogModel();

        if (setting('CronJob.maxLogsPerJob')) {
            $logs = $logModel->where('name', $data['name'])->findAll();
            // Make sure we have room for one more
            if ((is_countable($logs) ? count($logs) : 0) >= setting('CronJob.maxLogsPerJob')) {
                $forDelete = count($logs) - setting('CronJob.maxLogsPerJob');
                for ($i = 0; $forDelete >= $i; $i++) {
                    $logModel->delete($logs[$i]->id);
                }
            }
        }

        $logModel->insert($data);
    }

    public function getLogs(string $name): array
    {
        $logModel = new CronJobLogModel();
        return $logModel->where('name', $name)->orderBy('id', 'DESC')->findAll();
    }

    public function lastRun(string $name): string|Time
    {
        $logModel = new CronJobLogModel();
        $log = $logModel->where('name', $name)->orderBy('id', 'DESC')->first();

        if (empty($log)) {
            return '--';
        }

        return Time::parse($log->start_at);
    }
}
