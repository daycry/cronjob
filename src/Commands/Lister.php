<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Daycry\CronJob\Config\Services;

/**
 * Lists currently scheduled tasks.
 */
class Lister extends CronJobCommand
{
    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'cronjob:list';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Lists the cronjobs currently set to run.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'cronjob:list';

    /**
     * Lists upcoming tasks
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $this->getConfig();
        $settings = $this->getSettings();

        if (!$settings || (isset($settings->status) && $settings->status !== 'enabled')) {
            $this->tryToEnable();
            return false;
        }

        $scheduler = Services::scheduler();

        $this->config->init($scheduler);

        $tasks = [];

        foreach ($scheduler->getTasks() as $task) {
            $cron = new \Cron\CronExpression($task->getExpression());
            $nextRun = $cron->getNextRunDate()->format('Y-m-d H:i:s');
            $lastRun = $task->lastRun();

            $tasks[] = [
                'name'     => $task->name ?: $task->getAction(),
                'type'     => $task->getType(),
                'schedule' => $task->getExpression(),
                'last_run' => $lastRun instanceof Time ? $lastRun->format('Y-m-d H:i:s') : $lastRun,
                'next_run' => $nextRun
            ];
        }

        usort($tasks, function ($a, $b) {
            return ($a[ 'next_run' ] < $b[ 'next_run' ]) ? -1 : 1;
        });

        CLI::table(
            $tasks,
            [
                'Name',
                'Type',
                'Expression',
                'Last Run',
                'Next Run'
            ]
        );
    }
}
