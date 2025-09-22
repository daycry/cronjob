<?php

namespace Daycry\CronJob\Traits;

use CodeIgniter\I18n\Time;
use Daycry\CronJob\Exceptions\CronJobException;
use Daycry\CronJob\Interfaces\LoggerInterface;

/**
 * Trait LogTrait
 */
trait LogTrait
{
    protected ?Time $start              = null;
    protected ?Time $end                = null;
    protected ?Time $testTime           = null;
    protected ?LoggerInterface $handler = null;

    public function startLog(?string $start = null): self
    {
        $this->start = ($start) ? new Time($start) : Time::now();

        return $this;
    }

    public function endLog(?string $end = null): self
    {
        $this->end = ($end) ? new Time($end) : Time::now();

        return $this;
    }

    public function saveLog(?string $output = null, ?string $error = null): void
    {
        $localName = $this->getName();

        if ($this->config->logPerformance) {
            if (! $this->end) {
                $this->endLog(null);
            }

            $this->setHandler();

            // Truncate output & error if configured
            if ($this->config->maxOutputLength !== null && $this->config->maxOutputLength >= 0) {
                if ($output !== null) {
                    $len = \strlen($output);
                    if ($len > $this->config->maxOutputLength) {
                        $output = \substr($output, 0, $this->config->maxOutputLength) . "\n[truncated {$len} -> {$this->config->maxOutputLength} chars]";
                    }
                }
                if ($error !== null) {
                    $lenErr = \strlen($error);
                    if ($lenErr > $this->config->maxOutputLength) {
                        $error = \substr($error, 0, $this->config->maxOutputLength) . "\n[truncated {$lenErr} -> {$this->config->maxOutputLength} chars]";
                    }
                }
            }

            $data = [
                'name'        => $localName,
                'type'        => $this->getType(),
                'action'      => (\is_object($this->getAction())) ? \json_encode($this->getAction()) : $this->getAction(),
                'environment' => $this->environments ? \json_encode($this->environments) : null,
                'start_at'    => $this->start->format('Y-m-d H:i:s'),
                'end_at'      => $this->end->format('Y-m-d H:i:s'),
                'duration'    => $this->duration(),
                'output'      => $output ? \json_encode($output) : null,
                'error'       => $error ? \json_encode($error) : null,
                'test_time'   => ($this->testTime) ? $this->testTime->format('Y-m-d H:i:s') : null,
            ];

            $this->handler->save($data);
        }
    }

    public function getLogs(): array
    {
        if ($this->config->logPerformance === false) {
            return [];
        }
        $this->setHandler();
        $localName = $this->getName();

        return $this->handler->getLogs($localName);
    }

    public function duration(): string
    {
        $interval = $this->end->diff($this->start);

        return $interval->format('%H:%I:%S');
    }

    private function setHandler(): void
    {
        if (! $this->config->logSavingMethod || ! array_key_exists($this->config->logSavingMethod, $this->config->logSavingMethodClassMap)) {
            throw CronJobException::forInvalidLogType();
        }

        $class         = $this->config->logSavingMethodClassMap[$this->config->logSavingMethod];
        $this->handler = new $class();
    }
}
