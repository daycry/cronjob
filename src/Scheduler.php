<?php

declare(strict_types=1);

namespace Daycry\CronJob;

use Closure;
use CodeIgniter\Exceptions\RuntimeException;

/**
 * Class Scheduler
 *
 * Handles the registration and management of scheduled jobs.
 */
class Scheduler
{
    /**
     * @var list<Job> List of scheduled jobs
     */
    private array $tasks = [];

    /**
     * Returns the created Tasks.
     *
     * @return list<Job>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * Removes all scheduled tasks.
     */
    public function clearTasks(): void
    {
        $this->tasks = [];
    }

    /**
     * Find a task by its name.
     */
    public function findTaskByName(string $name): ?Job
    {
        foreach ($this->tasks as $task) {
            if ($task->getName() === $name) {
                return $task;
            }
        }

        return null;
    }

    /**
     * Schedules a closure to run.
     */
    public function call(Closure $func): Job
    {
        return $this->createTask('closure', $func);
    }

    /**
     * Schedules a console command to run.
     */
    public function command(string $command): Job
    {
        return $this->createTask('command', $command);
    }

    /**
     * Schedules a local function to be exec'd
     */
    public function shell(string $command): Job
    {
        return $this->createTask('shell', $command);
    }

    /**
     * Schedules an Event to trigger
     *
     * @param string $name Name of the event to trigger
     */
    public function event(string $name): Job
    {
        return $this->createTask('event', $name);
    }

    /**
     * Schedules a cURL command to a remote URL
     */
    public function url(string $url): Job
    {
        return $this->createTask('url', $url);
    }

    /**
     * Internal method to create and register a job.
     *
     * @param mixed $action
     */
    protected function createTask(string $type, $action): Job
    {
        $task          = new Job($type, $action);
        $this->tasks[] = $task;

        return $task;
    }

    /**
     * Remove a task by its name.
     *
     * @return bool True if removed, false if not found
     */
    public function removeTaskByName(string $name): bool
    {
        foreach ($this->tasks as $i => $task) {
            if ($task->getName() === $name) {
                array_splice($this->tasks, $i, 1);

                return true;
            }
        }

        return false;
    }

    /**
     * Check if a task exists by name.
     */
    public function hasTask(string $name): bool
    {
        foreach ($this->tasks as $task) {
            if ($task->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all task names.
     *
     * @return list<string>
     */
    public function getTaskNames(): array
    {
        $names = [];

        foreach ($this->tasks as $task) {
            $names[] = $task->getName();
        }

        return $names;
    }

    /**
     * Validate dependencies for all tasks (existence and cycles).
     * Throws exception if invalid.
     *
     * @throws RuntimeException
     */
    public function validateDependencies(): void
    {
        $names = $this->getTaskNames();

        // Existence check
        foreach ($this->tasks as $task) {
            $deps = $task->getDependsOn();
            if ($deps) {
                foreach ($deps as $dep) {
                    if (! in_array($dep, $names, true)) {
                        throw new RuntimeException("Dependency '{$dep}' for job '{$task->getName()}' does not exist.");
                    }
                }
            }
        }
        // Cycle check (DFS)
        $visited = [];
        $stack   = [];

        foreach ($this->tasks as $task) {
            if ($this->hasCycle($task, $visited, $stack)) {
                throw new RuntimeException("Circular dependency detected involving job '{$task->getName()}'.");
            }
        }
    }

    /**
     * Helper for cycle detection (DFS).
     */
    private function hasCycle(Job $job, array &$visited, array &$stack): bool
    {
        $name = $job->getName();
        if (isset($stack[$name])) {
            return true;
        }
        if (isset($visited[$name])) {
            return false;
        }
        $visited[$name] = true;
        $stack[$name]   = true;
        $deps           = $job->getDependsOn();
        if ($deps) {
            foreach ($deps as $dep) {
                $depJob = $this->findTaskByName($dep);
                if ($depJob && $this->hasCycle($depJob, $visited, $stack)) {
                    return true;
                }
            }
        }
        unset($stack[$name]);

        return false;
    }

    /**
     * Topological sort for job execution order (Kahn's algorithm).
     * Returns an array of jobs in execution order or throws on cycle.
     *
     * @return list<Job>
     *
     * @throws RuntimeException
     */
    public function getExecutionOrder(): array
    {
        $jobsByName = [];
        $inDegree   = [];
        $graph      = [];

        foreach ($this->tasks as $job) {
            $name              = $job->getName();
            $jobsByName[$name] = $job;
            $inDegree[$name]   = 0;
            $graph[$name]      = [];
        }

        foreach ($this->tasks as $job) {
            $name = $job->getName();
            $deps = $job->getDependsOn() ?? [];

            foreach ($deps as $dep) {
                if (! isset($jobsByName[$dep])) {
                    throw new RuntimeException("Dependency '{$dep}' for job '{$name}' does not exist.");
                }
                $graph[$dep][] = $name;
                $inDegree[$name]++;
            }
        }
        $queue = [];

        foreach ($inDegree as $name => $deg) {
            if ($deg === 0) {
                $queue[] = $name;
            }
        }
        $order = [];

        while ($queue) {
            $current = array_shift($queue);
            $order[] = $jobsByName[$current];

            foreach ($graph[$current] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }
        }
        if (count($order) !== count($jobsByName)) {
            throw new RuntimeException('Circular dependency detected in jobs.');
        }

        return $order;
    }
}
