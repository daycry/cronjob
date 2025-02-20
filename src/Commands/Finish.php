<?php

namespace Daycry\CronJob\Commands;

use CodeIgniter\I18n\Time;
use Daycry\CronJob\Job;
use Daycry\CronJob\JobRunner;
use Exception;

/**
 * Finish Running Command
 */
class Finish extends CronJobCommand
{
    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'cronjob:finish';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Finishes Running CronJob';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'cronjob:finish --type <task-type> --name <task-name>';

    /**
     * Enables task running
     *
     * @param array{
     *     type: string,
     *     name: string,
     * } $params
     *
     * @throws Exception
     */
    public function run(array $params): void
    {
        if (! isset($params['type']) || ! isset($params['name'])) {
            $this->showHelp();

            return;
        }

        $name = $params['name'];

        $task = new Job($params['type'], $name);

        $task->named($name);

        $info = $task->getIsRunningInfo();

        // If the task was not running we don't need to do anything
        if ($info === null) {
            return;
        }

        $startAt = $info['time'];

        $task
            ->startLog($startAt)
            ->endLog()
            ->saveRunningFlag(false);

        (new JobRunner())->sendCronJobFinishesEmailNotification(
            $task,
            new Time($startAt),
        );
    }
}
