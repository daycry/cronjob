<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

class Login extends BaseCronjob
{
    /**
     * Displays the form the login to the site.
     */
    public function index()
    {
        if ($this->session->get('cronjob')) {
            return redirect()->to('cronjob/dashboard');
        }

        return view(config('CronJob')->views['login'], $this->viewData);
    }

    public function validation()
    {
        $validation = \Config\Services::validation();
        $validation->setRule('username', 'Username', 'required');
        $validation->setRule('password', 'Password', 'required');

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('cronjob');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if ($username != config('CronJob')->username || $password != config('CronJob')->password) {
            return redirect()->to('cronjob');
        }

        $this->session->set('cronjob', true);
        return redirect()->to('cronjob/dashboard');
    }

    public function logout()
    {
        $this->session->remove('cronjob');
        return redirect()->to('cronjob');
    }
}
