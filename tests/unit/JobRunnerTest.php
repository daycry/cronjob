<?php

use CodeIgniter\CLI\CLI;
use Daycry\CronJob\Job;
use Daycry\CronJob\JobRunner;
use Tests\Support\TestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Daycry\CronJob\Config\CronJob as CronJobConfig;
use CodeIgniter\Test\Mock\MockInputOutput;

/**
 * @internal
 */
final class JobRunnerTest extends TestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
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

        $task1 = $this->createJob('Evento', 'task1', '12:00 am', 2);

        $runner = $this->getRunner([$task1]);
        $time = $this->getFormattedTime(0, 0);
        $runner->withTestTime($time)->run();
    }

    public function testRunWithEnvironment()
    {
        $task2 = $this->createJob('closure', 'task2', '12:00 am', 3, ['production']);

        $runner = $this->getRunner([$task2]);
        $time = $this->getFormattedTime(0, 0);
        $runner->withTestTime($time)->run();

        $this->assertCount(0, $runner->getJobs());
    }

    public function testRunWithSuccess()
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);

        $task1 = $this->createJob('closure', 'task1', '12:05 am', 2);
        $task2 = $this->createJob('closure', 'task2', '12:00 am', 3);

        $runner = $this->getRunner([$task1, $task2]);

        $time = $this->getFormattedTime(0, 0);
        $runner->withTestTime($time)->run();

        $output = $io->getOutput();

        $this->assertStringContainsString('task2', $output);
        $this->assertCount(1, $task2->getLogs());
        $this->assertCount(1, $runner->getJobs());
        $this->assertFileExists($this->config->filePath . 'task2/' . $this->config->fileName . '.json');

        // Remove MockInputOutput.
        CLI::resetInputOutput();
    }

    public function testRunWithOnlyJobsSuccess()
    {
        config('CronJob');

        $task1 = $this->createJob('closure', 'task1', '12:05 am', 2, []);
        $task2 = $this->createJob('closure', 'task2', '12:00 am', 3);

        $io = new MockInputOutput();
        CLI::setInputOutput($io);

        $runner = $this->getRunner([$task1, $task2]);
        $runner->only(['task1'])->run();
        $this->assertStringContainsString('task1', $io->getOutput());

        $this->assertCount(1, $runner->getJobs());
        $this->assertFileExists($this->config->filePath . 'task1/' . $this->config->fileName . '.json');

        // Remove MockInputOutput.
        CLI::resetInputOutput();
    }

    public function testRunWithEmptyNameSuccess()
    {
        $task2 = $this->createJob('closure', '', '12:00 am', 3);

        $io = new MockInputOutput();
        CLI::setInputOutput($io);

        $runner = $this->getRunner([$task2]);
        $time = $this->getFormattedTime(0, 0);
        $runner->withTestTime($time)->run();
        $output = $io->getOutput();

        $this->assertStringContainsString('closure', $task2->getName());

        $this->assertFileExists($this->config->filePath . $task2->getName() . '/' . $this->config->fileName . '.json');

        // Remove MockInputOutput.
        CLI::resetInputOutput();
    }

    public function testRunWithEmptyNameUrlSuccess()
    {
        $task = (new Job('url', 'https://google.es'))->daily('12:00 am');

        $runner = $this->getRunner([$task]);
        $time = $this->getFormattedTime(0, 0);
        $runner->withTestTime($time)->run();

        $this->assertFileExists($this->config->filePath . $task->getName() . '/' . $this->config->fileName . '.json');
    }

    protected function getRunner(array $tasks = []): JobRunner
    {
        $scheduler = service('scheduler');
        $this->setPrivateProperty($scheduler, 'tasks', $tasks);
        \Config\Services::injectMock('scheduler', $scheduler);

        return new JobRunner();
    }

    private function createJob(string $type, string $name, string $time, int $sleep, array $environments = []): Job
    {
        $job = (new Job($type, static function () use ($sleep, $name) {
            sleep($sleep);
            CLI::write($name);
        }))->daily($time)->named($name);

        if ($environments) {
            $job->environments($environments);
        }

        return $job;
    }

    private function getFormattedTime(int $hour, int $minute): string
    {
        return (new \DateTime('now'))->setTime($hour, $minute)->format('Y-m-d H:i:s');
    }

    private function cleanOutputBuffer(): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
