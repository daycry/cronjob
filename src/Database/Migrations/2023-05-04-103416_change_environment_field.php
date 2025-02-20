<?php

namespace Daycry\Cronjob\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class ChangeEnvironmentField extends Migration
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
            'environment' => [
                'name'       => 'environment',
                'type'       => 'varchar',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
        ];
        $this->forge->modifyColumn($this->config->tableName, $fields);
    }

    public function down()
    {
        /*$fields = [
            'environment' => [
                'null' => false
            ],
        ];
        $this->forge->modifyColumn($this->config->tableName, $fields);*/
    }
}
