<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;

class BaseCronJob extends BaseController
{
    protected $session;
    protected $viewData = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers[] = 'form';
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $config = config('CronJob');

        if (! $config->enableDashboard) {
            throw new PageNotFoundException();
        }

        $this->session = Services::session();
        $this->checkCronJobSession();
    }

    protected function checkCronJobSession()
    {
        $result = false;
        if ($this->session->get('cronjob')) {
            $result = true;
        }

        $this->viewData['cronjobLoggedIn'] = $result;

        return $result;
    }
}
