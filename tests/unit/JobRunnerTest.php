<?php

use Daycry\CronJob\Job;
use Daycry\CronJob\JobRunner;
use Tests\Support\TestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Daycry\CronJob\Config\CronJob as CronJobConfig;
/**
 * @internal
 */
final class JobRunnerTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected CronJobConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new CronJobConfig();
    }

    public function testRunWithNoTasks()
    {
        $this->assertNull($this->getRunner()->run());
    }

    public function testRunWithInvalidTasks()
    {
        $this->expectException(Daycry\CronJob\Exceptions\CronJobException::class);

        $task1 = (new Job('Evento', static function () {
            sleep(2);
            echo 'Task 1';
        }))->daily('12:00 am', true)->named('task1');

        $runner = $this->getRunner([$task1]);

        $time = (new \DateTime('now'))->setTime(00, 00)->format('Y-m-d H:i:s');
        $runner->withTestTime($time)->run();
    }

    public function testRunWithEnvironment()
    {
        $task2 = (new Job('closure', static function () {
            sleep(3);
            echo 'Task 2';
        }))->daily('12:00 am')->named('task2')->environments(array('production'));

        ob_start();

        $runner = $this->getRunner([$task2]);
        $time = (new \DateTime('now'))->setTime(00, 00)->format('Y-m-d H:i:s');
        $runner->withTestTime($time)->run();

        ob_end_clean();

        $this->assertCount(0, $runner->getJobs());
    }

    public function testRunWithSuccess()
    {

        /** @var Job $task1 */
        $task1 = (new Job('closure', static function () {
            sleep(2);
            echo 'Task 1';
        }))->daily('12:05 am', true)->named('task1');

        /** @var Job $task2 */
        $task2 = (new Job('closure', static function () {
            sleep(3);
            echo 'Task 2';
        }))->daily('12:00 am')->named('task2');

        ob_start();

        $runner = $this->getRunner([$task1, $task2]);

        $time = (new \DateTime('now'))->setTime(00, 00)->format('Y-m-d H:i:s');
        $runner->withTestTime($time)->run();

        // Only task 2 should have ran
        $this->assertSame('Task 2', $this->getActualOutput());

        ob_end_clean();

        $this->assertCount(1, $task2->getLogs());
        $this->assertCount(1, $runner->getJobs());
        $this->assertTrue(is_dir($this->config->filePath));
        $this->assertTrue(is_file($this->config->filePath . 'task2' . '/' . $this->config->fileName . '.json'));
    }

    public function testRunWithOnlyJobsSuccess()
    {
        $config = config('CronJob');

        $task1 = (new Job('closure', static function () {
            sleep(2);
            echo 'Task 1';
        }))->daily('12:05 am', true)->named('task1');

        $task2 = (new Job('closure', static function () {
            sleep(3);
            echo 'Task 2';
        }))->daily('12:00 am')->named('task2');

        ob_start();

        $runner = $this->getRunner([$task1, $task2]);
        $runner->only(['task1'])->run();

        // Only task 2 should have ran
        $this->assertSame('Task 1', $this->getActualOutput());

        ob_end_clean();

        $this->assertCount(1, $runner->getJobs());
        $this->assertTrue(is_dir($this->config->filePath));
        $this->assertTrue(is_file($this->config->filePath . 'task1' . '/' . $this->config->fileName . '.json'));
    }

    public function testRunWithEmptyNameSuccess()
    {
        $config = config('CronJob');

        $task2 = (new Job('closure', static function () {
            sleep(3);
            echo 'Task 2';
        }))->daily('12:00 am');

        ob_start();

        $runner = $this->getRunner([$task2]);

        $time = (new \DateTime('now'))->setTime(00, 00)->format('Y-m-d H:i:s');
        $runner->withTestTime($time)->run();

        // Only task 2 should have ran
        $this->assertSame('Task 2', $this->getActualOutput());

        ob_end_clean();

        $this->assertTrue(is_dir($this->config->filePath));
        $this->assertTrue(is_file($this->config->filePath . $task2->getName() . '/' . $this->config->fileName . '.json'));
    }

    public function testRunWithEmptyNameUrlSuccess()
    {
        $config = config('CronJob');

        $task = (new Job('url', 'https://google.es'))->daily('12:00 am');

        ob_start();

        $runner = $this->getRunner([$task]);

        $time = (new \DateTime('now'))->setTime(00, 00)->format('Y-m-d H:i:s');
        $runner->withTestTime($time)->run();

        ob_end_clean();

        $this->assertTrue(is_dir($this->config->filePath));
        $this->assertTrue(is_file($this->config->filePath . $task->getName() . '/' . $this->config->fileName . '.json'));
    }

    protected function getRunner(array $tasks = [])
    {
        $scheduler = service('scheduler');
        $this->setPrivateProperty($scheduler, 'tasks', $tasks);
        \Config\Services::injectMock('scheduler', $scheduler);

        return new JobRunner();
    }
}
