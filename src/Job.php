<?php

namespace Daycry\CronJob;

use CodeIgniter\Events\Events;
use Config\Services;
use Daycry\CronJob\Config\CronJob as BaseConfig;
use Daycry\CronJob\Exceptions\CronJobException;
use Daycry\CronJob\Traits\ActivityTrait;
use Daycry\CronJob\Traits\FrequenciesTrait;
use Daycry\CronJob\Traits\InteractsWithSpark;
use Daycry\CronJob\Traits\LogTrait;
use Daycry\CronJob\Traits\StatusTrait;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use SplFileObject;

/**
 * Class Job
 *
 * Represents a single task that should be scheduled
 * and run periodically.
 *
 * @property-read mixed  $action
 * @property-read array  $environments
 * @property-read string $name
 * @property-read string $type
 * @property-read array  $types
 */
class Job
{
    use FrequenciesTrait;
    use LogTrait;
    use ActivityTrait;
    use StatusTrait;
    use InteractsWithSpark;

    protected BaseConfig $config;

    /**
     * Supported action types.
     *
     * @var list<string>
     */
    protected $types = [
        'command',
        'shell',
        'closure',
        'event',
        'url',
    ];

    /**
     * The type of cron run.
     */
    protected string $runType = 'multiple';

    /**
     * If the job will run as a background process
     */
    protected bool $runInBackground = false;

    /**
     * The type of action.
     */
    protected string $type;

    /**
     * The actual content that should be run.
     */
    protected mixed $action;

    /**
     * If not empty, lists the allowed environments
     * this can run in.
     */
    protected array $environments = [];

    /**
     * The alias this task can be run by
     */
    protected ?string $name = null;

    /**
     * @param mixed $action
     *
     * @throws CronJobException
     */
    public function __construct(string $type, $action)
    {
        if (! in_array($type, $this->types, true)) {
            throw CronJobException::forInvalidTaskType($type);
        }
        $this->config = config('CronJob');
        $this->type   = $type;
        $this->action = $action;
    }

    /**
     * Set the name to reference this task by
     *
     * @return $this
     */
    public function named(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the saved action.
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Runs this Task's action.
     *
     * @throws CronJobException
     */
    public function run(): mixed
    {
        $method = 'run' . ucfirst($this->type);
        // @codeCoverageIgnoreStart
        if (! method_exists($this, $method)) {
            throw CronJobException::forInvalidTaskType($this->type);
        }
        // @codeCoverageIgnoreEnd

        return $this->{$method}();
    }

    /**
     * Restricts this task to run within only
     * specified environements.
     *
     * @param mixed ...$environments
     *
     * @return $this
     */
    public function environments(...$environments): self
    {
        $this->environments = $environments;

        return $this;
    }

    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * Checks if it runs within the specified environment.
     */
    protected function runsInEnvironment(string $environment): bool
    {
        // If nothing is specified then it should run
        if (empty($this->environments)) {
            return true;
        }

        return in_array($environment, $this->environments, true);
    }

    /**
     * Runs a framework Command.
     *
     * @return string Buffered output from the Command
     *
     * @throws InvalidArgumentException
     */
    protected function runCommand(): string
    {
        if (! $this->shouldRunInBackground()) {
            return command($this->getAction());
        }

        $output = $this->runCommandInBackground();

        return is_string($output) ? $output : '';
    }

    private function runCommandInBackground(): bool|string
    {
        $this->createFoldersIfNeeded();

        $runCommand = $this->sparkCommandInBackground($this->getAction());

        $afterRunCommand = $this->sparkCommandInBackground(
            "cronjob:finish --name {$this->getName()} --type {$this->getType()}",
        );

        return exec("{$runCommand} && {$afterRunCommand}");
    }

    /**
     * Executes a shell script.
     *
     * @return array Lines of output from exec
     */
    protected function runShell(): array
    {
        exec($this->getAction(), $output);

        return $output;
    }

    /**
     * Calls a Closure.
     *
     * @return mixed The result of the closure
     */
    protected function runClosure()
    {
        return $this->getAction()->__invoke();
    }

    /**
     * Triggers an Event.
     *
     * @return bool Result of the trigger
     */
    protected function runEvent(): bool
    {
        return Events::trigger($this->getAction());
    }

    /**
     * Queries a URL.
     *
     * @return mixed|string Body of the Response
     */
    protected function runUrl()
    {
        $response = Services::curlrequest()->request('GET', $this->getAction());

        return $response->getBody();
    }

    /**
     * Builds a unique name for the task.
     * Used when an existing name doesn't exist.
     *
     * @return string
     *
     * @throws ReflectionException
     */
    protected function buildName()
    {
        // Get a hash based on the action
        // Closures cannot be serialized so do it the hard way
        if ($this->getType() === 'closure') {
            $ref  = new ReflectionFunction($this->getAction());
            $file = new SplFileObject($ref->getFileName());
            $file->seek($ref->getStartLine() - 1);
            $content = '';

            while ($file->key() < $ref->getEndLine()) {
                $content .= $file->current();
                $file->next();
            }
            $actionString = json_encode([
                $content,
                $ref->getStaticVariables(),
            ]);
        } else {
            $actionString = serialize($this->getAction());
        }

        // Get a hash based on the expression
        $expHash = $this->getExpression();

        return $this->getType() . '_' . md5($actionString . '_' . $expHash);
    }

    public function getName()
    {
        if (empty($this->name)) {
            return $this->buildName();
        }

        return $this->name;
    }

    /**
     * Set the runType of task
     *
     * @return $this
     */
    public function setRunType(string $runType): Job
    {
        $this->runType = $runType;

        return $this;
    }

    /**
     * Returns the runType.
     */
    public function getRunType(): string
    {
        return $this->runType;
    }

    /**
     * Mark job to run in background
     *
     * @return $this
     */
    public function runInBackground(): Job
    {
        // Only commands are currently able to execute in background
        if ($this->type === 'command') {
            $this->runInBackground = true;
        }

        return $this;
    }

    /**
     * If the job will run in the background
     */
    public function shouldRunInBackground(): bool
    {
        return $this->runInBackground;
    }
}
