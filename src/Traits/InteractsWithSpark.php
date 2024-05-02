<?php

namespace Daycry\CronJob\Traits;

trait InteractsWithSpark
{
    public function sparkPath(): string
    {
        return FCPATH . '../spark';
    }

    public function sparkCommandInBackground(string $command): string
    {
        return sprintf(
            '%s %s %s',
            PHP_BINARY,
            $this->sparkPath(),
            $command,
        );
    }
}