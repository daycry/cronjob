<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use Daycry\CronJob\Config\Services;

class Dashboard extends BaseCronJob
{
    /**
     * Displays the form the login to the site.
     */
    public function index()
    {
        if (!$this->checkCronJobSession()) {
            return redirect()->to('cronjob');
        }

        $config = config('CronJob');
        $scheduler = Services::scheduler();
        $config->init($scheduler);

        $this->viewData['jobs'] = $scheduler->getTasks();

        return view($config->views['dashboard'], $this->viewData);
    }
}
