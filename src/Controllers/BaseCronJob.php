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
        $config = config('CronJob');

        if ($this->session->get('cronjob')) {
            // Verificar timeout de sesión
            $loginTime = $this->session->get('cronjob_login_time');
            if ($loginTime && (time() - $loginTime) > $config->sessionTimeout) {
                $this->session->destroy();
                log_message('info', 'CronJob: Sesión expirada por timeout');
                return false;
            }

            // Verificar IP (opcional - podría ser problemático con proxies)
            $sessionIP = $this->session->get('cronjob_ip');
            $currentIP = $this->request->getIPAddress();
            if ($sessionIP && $sessionIP !== $currentIP) {
                log_message('warning', 'CronJob: Intento de acceso desde IP diferente. IP de sesión: ' . $sessionIP . ', IP actual: ' . $currentIP);
                // Opcional: descomentar para forzar logout en cambio de IP
                // $this->session->destroy();
                // return false;
            }

            $result = true;
        }

        $this->viewData['cronjobLoggedIn'] = $result;

        return $result;
    }
}
