<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use Daycry\CronJob\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;

class Job extends BaseCronJob
{
    /**
     * Displays the form the login to the site.
     */
    public function index(string $jobName)
    {
        if (! $this->checkCronJobSession()) {
            return redirect()->to('cronjob');
        }

        $scheduler = $this->initializeScheduler();

        $job = $this->findJobByName($scheduler, $jobName);

        if (! $job) {
            throw new PageNotFoundException();
        }

        $this->viewData['logs'] = $job->getLogs();

        return view(config('CronJob')->views['logs'], $this->viewData);
    }

    /**
     * Initialize the scheduler with the configuration.
     */
    private function initializeScheduler()
    {
        $config = config('CronJob');
        $scheduler = Services::scheduler();
        $config->init($scheduler);

        return $scheduler;
    }

    /**
     * Find a job by its name from the scheduler.
     */
    private function findJobByName($scheduler, string $jobName)
    {
        foreach ($scheduler->getTasks() as $job) {
            if ($job->getName() === $jobName) {
                return $job;
            }
        }

        return false;
    }
}
