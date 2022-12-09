<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use App\Controllers\BaseController;

class Login extends BaseController
{
    protected $helpers = ['form'];

    /**
     * Displays the form the login to the site.
     */
    public function index()
    {
        $session = \Config\Services::session();
        
        if( $session->get('cronjob') )
        {
            return redirect()->to('cronjob/dashboard'); 
        }

        return view(config('CronJob')->views['login']);
    }

    public function validation()
    {
        $session = \Config\Services::session();

        $validation = \Config\Services::validation();
        $validation->setRule('username', 'Username', 'required');
        $validation->setRule('password', 'Password', 'required');

        if( !$validation->withRequest($this->request)->run() )
        {
            return redirect()->to('cronjob');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if( $username != config('CronJob')->username || $password != config('CronJob')->password )
        {
            return redirect()->to('cronjob'); 
        }

        $session->set('cronjob', true);
    }
}