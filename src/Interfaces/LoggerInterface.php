<?php

namespace Daycry\CronJob\Interfaces;

use CodeIgniter\I18n\Time;

interface LoggerInterface
{
    public function save(array $data): void;

    public function getLogs(string $name): array;

    public function lastRun(string $name): string|Time;
}
