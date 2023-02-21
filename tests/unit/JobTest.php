<?php

use CodeIgniter\I18n\Time;
use Daycry\CronJob\Job;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Filters\CITestStreamFilter;
use CodeIgniter\Test\CIUnitTestCase as TestCase;
use CodeIgniter\Test\StreamFilterTrait;

/**
 * @internal
 */
final class JobTest extends TestCase
{
    use DatabaseTestTrait, StreamFilterTrait;

    protected $refresh   = true;
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = config('CronJob');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getBuffer(): string
    {
        return CITestStreamFilter::$buffer;
    }

    public function testNamed()
    {
        $task = new Job('command', 'foo:bar');

        // Will build a random name
        $this->assertSame(0, strpos($task->name, 'command_'));

        $task = (new Job('command', 'foo:bar'))->named('foo');

        $this->assertSame('foo', $task->name);
    }

    public function testConstructSavesAction()
    {
        $task = new Job('command', 'foo:bar');

        $result = $this->getPrivateProperty($task, 'action');

        $this->assertSame('foo:bar', $result);
    }

    public function testGetAction()
    {
        $task = new Job('command', 'foo:bar');

        $this->assertSame('foo:bar', $task->getAction());
    }

    public function testGetType()
    {
        $task = new Job('command', 'foo:bar');

        $this->assertSame('command', $task->getType());
    }

    public function testCommandRunsCommand()
    {
        $task = new Job('command', 'jobs:test');

        $task->run();

        $this->assertStringContainsString(
            'Commands can output text.',
            $this->getBuffer()
        );
    }

    public function testShouldRunSimple()
    {
        $task = (new Job('command', 'jobs:test'))->daily('11:00 pm');

        $now = (new \DateTime('now'))->format('Y-m-d');

        $this->assertTrue($task->shouldRun(new \DateTime($now . ' 23:00:00')));
        $this->assertFalse($task->shouldRun(new \DateTime($now . ' 23:05:00')));
    }

    public function testShouldRunWithEnvironments()
    {
        $originalEnv               = $_SERVER['CI_ENVIRONMENT'];
        $_SERVER['CI_ENVIRONMENT'] = 'development';

        $task = (new Job('command', 'jobs:test'))->environments('development');

        $now = (new \DateTime('now'))->format('Y-m-d');

        $this->assertTrue($task->shouldRun(new \DateTime($now . ' 23:00:00')));

        $_SERVER['CI_ENVIRONMENT'] = 'production';

        $this->assertFalse($task->shouldRun(new \DateTime($now . ' 23:00:00')));

        $_SERVER['CI_ENVIRONMENT'] = $originalEnv;
    }

    public function testLastRun()
    {
        $job = new Job('closure', static fn () => 1);
        $job->named('foo');

        // Should be dashes when not ran
        $this->assertSame('--', $job->lastRun());

        $date = date('Y-m-d H:i:s');

        $data = [
            'name'     => $job->name,
            'type'     => $job->getType(),
            'action'   => (\is_object($job->getAction())) ? \json_encode($job->getAction()) : $job->getAction(),
            'environment' => \json_encode($job->environments),
            'start_at'    => $date,
            'duration' => '00:00:11',
            'output'   => null,
            'error'    => null,
            'test_time' => null
        ];

        $path = $this->config->filePath . $job->name;
        $fileName = $path . '/' . $this->config->fileName . '.json';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $logs = array();

        // Add the log to the top of the array
        array_unshift($logs, $data);

        file_put_contents(
            $fileName,
            json_encode(
                $logs,
                JSON_PRETTY_PRINT
            )
        );

        // Should return the current time
        $this->assertInstanceOf(\CodeIgniter\I18n\Time::class, $job->lastRun()); // @phpstan-ignore-line
        $this->assertSame($date, $job->lastRun()->format('Y-m-d H:i:s'));
    }
}
