<?php

namespace Tests\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class LoginTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();
    }

    public function testShowLoginDisables()
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);

        $result = $this->call('get', 'cronjob');

        $this->assertTrue($result->isOK());
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

    public function testLoginValidationSuccess()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('post', 'cronjob/login/validation', [
            'username'  => 'admin',
            'password' => 'admin',
        ]);

        $result->assertRedirectTo('cronjob/dashboard');
    }
}
