<?php

namespace Tests\Controllers;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Daycry\CronJob\Job;

class JobTesting extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();
    }

    public function testShowLogsWithoutLogin()
    {
        putenv('cronJob.enableDashboard=true');

        $result = $this->call('get', 'cronjob/job/1234/logs');

        $result->assertRedirectTo('cronjob');
    }

    public function testShowLogsWithoutJob()
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);

        putenv('cronJob.enableDashboard=true');

        $session = \Config\Services::session();
        $session->set('cronjob', true);
        
        $result = $this->withSession()->call('get', 'cronjob/job/1234/logs');
    }

    public function testShowLogsWithJob()
    {
        putenv('cronJob.enableDashboard=true');

        $job = (new Job('url', 'https://google.es'))->daily('12:00 am')->named('1234');

        $scheduler = service('scheduler');
        $this->setPrivateProperty($scheduler, 'tasks', [$job]);
        \Config\Services::injectMock('scheduler', $scheduler);

        $session = \Config\Services::session();
        $session->set('cronjob', true);
        $result = $this->withSession()->call('get', 'cronjob/job/1234/logs');

        $this->assertTrue($result->isOK());
    }
}
