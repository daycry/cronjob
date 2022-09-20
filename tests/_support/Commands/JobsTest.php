<?php

namespace Tests\Support\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * @internal
 */
final class JobsTest extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'jobs:test';
    protected $description = 'Tests Jobs';
    protected $usage       = 'jobs:test';

    public function run(array $params = [])
    {
        CLI::write('Commands can output text.');
    }
}
