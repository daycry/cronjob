<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use App\Controllers\BaseController;
use Daycry\CronJob\Config\Services;

class Dashboard extends BaseController
{
    protected $helpers = ['form'];

    /**
     * Displays the form the login to the site.
     */
    public function index()
    {
        $session = \Config\Services::session();
        $config = config('CronJob');
        if (!$session->get('cronjob')) {
            return redirect()->to('cronjob');
        }

        $data = [];
        $scheduler = Services::scheduler();
        $config->init($scheduler);

        $data['jobs'] = $scheduler->getTasks();

        return view(config('CronJob')->views['dashboard'], $data);
    }
}
