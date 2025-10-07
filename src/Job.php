<?php

declare(strict_types=1);

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
    protected array $types = [
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
     * List of job dependencies
     *
     * @var list<string>|null
     */
    protected ?array $dependsOn = null;

    /**
     * The maximum number of retries for this job.
     */
    protected ?int $maxRetries = null;

    /**
     * The timeout (in seconds) for this job.
     */
    protected ?int $timeout = null;
    /**
     * Cached computed name hash to avoid recalculating reflection/serialization repeatedly.
     */
    private ?string $computedName = null;

    /**
     * Job constructor.
     *
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
        if (! method_exists($this, $method)) {
            throw CronJobException::forInvalidTaskType($this->type);
        }

        return $this->{$method}();
    }

    /**
     * Restricts this task to run within only specified environments.
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

    /**
     * Returns the environments.
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    /**
     * Checks if it runs within the specified environment.
     */
    protected function runsInEnvironment(string $environment): bool
    {
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
     * @throws CronJobException
     */
    protected function runShell(): array
    {
        // Validar y escapar el comando para prevenir inyección
        $command = $this->getAction();

        // Validar que el comando esté en una lista blanca o tenga caracteres seguros
        if (!$this->isValidCommand($command)) {
            throw CronJobException::forInvalidCommand($command);
        }

        // Escapar el comando para mayor seguridad
        $escapedCommand = escapeshellcmd($command);

        exec($escapedCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw CronJobException::forCommandExecutionFailed($command, $returnCode);
        }

        return $output;
    }

    /**
     * Validates if a command is safe to execute
     */
    private function isValidCommand(string $command): bool
    {
        // Lista de comandos/caracteres peligrosos
        $dangerousPatterns = [
            '/[;&|`$(){}[\]<>]/',  // Caracteres de control de shell
            '/\b(rm|del|format|fdisk|mkfs)\b/i',  // Comandos destructivos
            '/\b(wget|curl|nc|netcat)\b.*http/i',  // Comandos de red sospechosos
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return false;
            }
        }

        return true;
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
     * @throws CronJobException
     */
    protected function runUrl()
    {
        $url = $this->getAction();

        // Validar URL para prevenir SSRF
        if (!$this->isValidUrl($url)) {
            throw CronJobException::forInvalidUrl($url);
        }

        try {
            $response = Services::curlrequest([
                'timeout' => 30,
                'verify' => true, // Verificar certificados SSL
                'allow_redirects' => [
                    'max' => 3, // Limitar redirects
                    'strict' => true
                ]
            ])->request('GET', $url);

            return $response->getBody();
        } catch (\Exception $e) {
            throw CronJobException::forUrlRequestFailed($url, $e->getMessage());
        }
    }

    /**
     * Validates if a URL is safe to request (prevents SSRF)
     */
    private function isValidUrl(string $url): bool
    {
        // Validar formato de URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsedUrl = parse_url($url);

        // Solo permitir HTTP/HTTPS
        if (!in_array($parsedUrl['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }

        // Resolver IP del host
        $host = $parsedUrl['host'] ?? '';
        $ip = gethostbyname($host);

        // Bloquear IPs privadas y localhost para prevenir SSRF
        if ($this->isPrivateOrLocalIp($ip)) {
            return false;
        }

        return true;
    }

    /**
     * Check if IP is private or local (SSRF protection)
     */
    private function isPrivateOrLocalIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
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

    /**
     * @return string
     */
    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }
        // Cache computed hash so multiple calls don't repeat reflection work
        if ($this->computedName === null) {
            $this->computedName = $this->buildName();
        }

        return $this->computedName;
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

    /**
     * Set dependencies for this job.
     *
     * @return $this
     */
    public function dependsOn(array|string $jobNames): self
    {
        $this->dependsOn = is_array($jobNames) ? $jobNames : [$jobNames];

        return $this;
    }

    /**
     * Get dependencies for this job.
     */
    public function getDependsOn(): ?array
    {
        return $this->dependsOn;
    }

    /**
     * Set the maximum number of retries for this job.
     *
     * @return $this
     */
    public function maxRetries(int $retries): self
    {
        $this->maxRetries = $retries;

        return $this;
    }

    /**
     * Set the timeout (in seconds) for this job.
     *
     * @return $this
     */
    public function timeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get the maximum number of retries for this job.
     */
    public function getMaxRetries(): ?int
    {
        return $this->maxRetries ?? null;
    }

    /**
     * Get the timeout (in seconds) for this job.
     */
    public function getTimeout(): ?int
    {
        return $this->timeout ?? null;
    }
}
