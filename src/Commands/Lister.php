<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;
use Cron\CronExpression;
use Daycry\CronJob\Config\CronJob;
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
     */
    public function run(array $params)
    {
        $this->getConfig();
        $settings = $this->getSettings();

        if (! $settings || (isset($settings->status) && $settings->status !== 'enabled')) {
            $this->tryToEnable();

            return false;
        }

        $scheduler = Services::scheduler();

        /** @var CronJob $config */
        $config = config('CronJob');
        $config->init($scheduler);

        $tasks = [];

        foreach ($scheduler->getTasks() as $task) {
            $cron    = new CronExpression($task->getExpression());
            $nextRun = $cron->getNextRunDate()->format('Y-m-d H:i:s');
            $lastRun = $task->lastRun();

            $tasks[] = [
                'name'     => $task->getName() ?: $task->getAction(),
                'type'     => $task->getType(),
                'schedule' => $task->getExpression(),
                'last_run' => $lastRun instanceof Time ? $lastRun->format('Y-m-d H:i:s') : $lastRun,
                'next_run' => $nextRun,
            ];
        }

        usort($tasks, static fn ($a, $b) => ($a['next_run'] < $b['next_run']) ? -1 : 1);

        CLI::table(
            $tasks,
            [
                'Name',
                'Type',
                'Expression',
                'Last Run',
                'Next Run',
            ],
        );
    }
}
