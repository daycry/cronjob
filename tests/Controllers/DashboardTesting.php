<?php

namespace Tests\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class DashboardTesting extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();
    }

    public function testShowDashboardWithoutLogin()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('get', 'cronjob/dashboard');

        $result->assertRedirectTo('cronjob');
    }

    public function testShowDashboardWithLogin()
    {
        putenv('cronJob.enableDashboard=true');

        $session = \Config\Services::session();
        $session->set('cronjob', true);
        $result = $this->withSession()->call('get', 'cronjob/dashboard');

        $this->assertTrue($result->isOK());
    }
}
