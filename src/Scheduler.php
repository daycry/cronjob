<?php

namespace Daycry\CronJob;

use Closure;

class Scheduler
{
    protected array $tasks = [];

    /**
     * Returns the created Tasks.
     *
     * @return list<Job>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    // --------------------------------------------------------------------

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

    // --------------------------------------------------------------------

    /**
     * @param mixed $action
     *
     * @return Task
     */
    protected function createTask(string $type, $action)
    {
        $task          = new Job($type, $action);
        $this->tasks[] = $task;

        return $task;
    }
}
