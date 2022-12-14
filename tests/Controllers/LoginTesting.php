<?php

namespace Tests\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class LoginTesting extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();
    }

    public function testShowLoginDisabled()
    {
        putenv('cronJob.enableDashboard=false');

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);

        $result = $this->call('get', 'cronjob');
    }

    public function testShowLoginEnabled()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('get', 'cronjob');

        $this->assertTrue($result->isOK());
    }

    public function testShowLoginLogged()
    {
        putenv('cronJob.enableDashboard=true');

        $session = \Config\Services::session();
        $session->set('cronjob', true);
        $result = $this->withSession()->call('get', 'cronjob');

        $result->assertRedirectTo('cronjob/dashboard');
        $session->remove('cronjob');
    }

    public function testLoginValidationError()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('post', 'cronjob/login/validation', [
            'username1'  => 'admin',
            'password' => 'admin',
        ]);

        $result->assertRedirectTo('cronjob');
    }

    public function testLoginUsernameError()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('post', 'cronjob/login/validation', [
            'username'  => 'admin1',
            'password' => 'admin',
        ]);

        $result->assertRedirectTo('cronjob');
    }

    public function testLoginValidationSuccess()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('post', 'cronjob/login/validation', [
            'username'  => 'admin',
            'password' => 'admin',
        ]);

        $result->assertRedirectTo('cronjob/dashboard');
    }

    public function testLoginLogout()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('get', 'cronjob/login/logout');

        $result->assertRedirectTo('cronjob');
    }
}
