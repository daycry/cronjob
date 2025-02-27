<?php

namespace Daycry\Cronjob\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class ChangeLogToLongText extends Migration
{
    protected $config;

    public function __construct(?Forge $forge = null)
    {
        $this->config  = config('CronJob');
        $this->DBGroup = $this->config->databaseGroup;

        parent::__construct($forge);
    }

    public function up()
    {
        $fields = [
            'output' => [
                'type' => 'longtext',
            ],
            'error' => [
                'type' => 'longtext',
            ],
        ];
        $this->forge->modifyColumn($this->config->tableName, $fields);
    }

    public function down()
    {
        $fields = [
            'output' => [
                'type'       => 'varchar',
                'constraint' => 255,
            ],
            'error' => [
                'type'       => 'varchar',
                'constraint' => 255,
            ],
        ];
        $this->forge->modifyColumn($this->config->tableName, $fields);
    }
}
