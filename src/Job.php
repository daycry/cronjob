<?php

namespace Daycry\CronJob;

use CodeIgniter\Events\Events;
use Daycry\CronJob\Exceptions\CronJobException;
use Config\Services;
use Daycry\CronJob\Traits\ActivityTrait;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use SplFileObject;
use Daycry\CronJob\Traits\FrequenciesTrait;
use Daycry\CronJob\Traits\LogTrait;
use Daycry\CronJob\Traits\StatusTrait;

/**
 * Class Job
 *
 * Represents a single task that should be scheduled
 * and run periodically.
 *
 * @property-read array $types
 * @property-read string $type
 * @property-read mixed $action
 * @property-read array $environments
 * @property-read string $name
 */
class Job
{
    use FrequenciesTrait;
    use LogTrait;
    use ActivityTrait;
    use StatusTrait;

    /**
     * Supported action types.
     *
     * @var string[]
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
     *
     * @var string
     */
    protected string $runType = 'multiple';

    /**
     * The type of action.
     *
     * @var string
     */
    protected string $type;

    /**
     * The actual content that should be run.
     *
     * @var mixed
     */
    protected mixed $action;

    /**
     * If not empty, lists the allowed environments
     * this can run in.
     *
     * @var array
     */
    protected array $environments = [];

    /**
     * The alias this task can be run by
     *
     * @var string
     */
    protected ?string $name = null;

    /**
     * @param mixed  $action
     * @param string $type
     *
     * @throws CronJobException
     */
    public function __construct(string $type, $action)
    {
        helper('setting');
        
        if (!in_array($type, $this->types, true)) {
            throw CronJobException::forInvalidTaskType($type);
        }

        $this->type   = $type;
        $this->action = $action;
    }

    /**
     * Set the name to reference this task by
     *
     * @param string $name
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
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the saved action.
     *
     * @return mixed
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
        if (!method_exists($this, $method)) {
            throw CronJobException::forInvalidTaskType($this->type);
        }
        // @codeCoverageIgnoreEnd

        return $this->$method();
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
     *
     * @param string $environment
     *
     * @return boolean
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
     * @throws \InvalidArgumentException
     */
    protected function runCommand(): string
    {
        return command($this->getAction());
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
     * @return boolean Result of the trigger
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
     * @throws \ReflectionException
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
                $ref->getStaticVariables()
            ]);
        } else {
            $actionString = serialize($this->getAction());
        }

        // Get a hash based on the expression
        $expHash = $this->getExpression();

        return  $this->getType() . '_' . md5($actionString . '_' . $expHash);
    }

    public function getName()
    {
        if(empty($this->name)) {
            return $this->buildName();
        }

        return $this->name;
    }

    /**
     * Set the runType of task
     *
     * @param string $runType
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
     *
     * @return string
     */
    public function getRunType(): string
    {
        return $this->runType;
    }
}
