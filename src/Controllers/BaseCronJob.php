<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use App\Controllers\BaseController;
use Daycry\CronJob\Config\Services;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
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

        if (!$config->enableDashboard) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException();
        }

        $this->session = \Config\Services::session();
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