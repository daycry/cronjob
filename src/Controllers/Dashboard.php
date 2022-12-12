<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use Daycry\CronJob\Config\Services;

class Dashboard extends BaseCronjob
{
    /**
     * Displays the form the login to the site.
     */
    public function index()
    {
        $config = config('CronJob');
        if (!$this->checkCronJobSession()) {
            return redirect()->to('cronjob');
        }

        $data = [];
        $scheduler = Services::scheduler();
        $config->init($scheduler);

        $this->viewData['jobs'] = $scheduler->getTasks();

        return view(config('CronJob')->views['dashboard'], $this->viewData);
    }
}
