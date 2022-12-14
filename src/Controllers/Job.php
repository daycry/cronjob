<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use Daycry\CronJob\Config\Services;

class Job extends BaseCronJob
{
    /**
     * Displays the form the login to the site.
     */
    public function index(string $jobName)
    {
        if (!$this->checkCronJobSession()) {
            return redirect()->to('cronjob');
        }

        $config = config('CronJob');

        $scheduler = Services::scheduler();
        $config->init($scheduler);

        $result = false;
        foreach ($scheduler->getTasks() as $job) {
            if ($job->name === $jobName) {
                $result = $job;
                break;
            }
        }

        if (!$result) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }

        $this->viewData['logs'] = $result->getLogs();

        return view($config->views['logs'], $this->viewData);
    }
}
