<?php namespace Daycry\Cronjob\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCronjobTables extends Migration
{
    public function up()
    {
        $config = $this->_getConfig();

        /*
         * Jobs
         */
        $this->forge->addField([
            'id'                    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'                  => ['type' => 'varchar', 'constraint' => 50, 'null' => true, 'default' => null],
            'type'                  => ['type' => 'varchar', 'constraint' => 25, 'null' => false],
            'action'                => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
            'environment'           => ['type' => 'varchar', 'constraint' => 100, 'null' => false],
            'output'                => ['type' => 'varchar', 'constraint' => 255,'null' => true, 'default' => null],
            'error'                 => ['type' => 'varchar', 'constraint' => 255,'null' => true, 'default' => null],
            'start_at'              => ['type' => 'datetime', 'null' => false],
            'end_at'                => ['type' => 'datetime', 'null' => false],
            'duration'              => ['type' => 'time', 'null' => false],
            'test_time'             => ['type' => 'datetime', 'null' => true, 'default' => null],
            'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'deleted_at'       => ['type' => 'datetime', 'null' => true, 'default' => null]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('deleted_at');

        $this->forge->createTable($config->tableName, true);
    }

    public function down()
    {
        $config = $this->_getConfig();

		$this->forge->dropTable($config->tableName, true);
    }

    private function _getConfig()
    {
        $config = config( 'CronJob' );

        if( !$config )
        {
            $config = new \Daycry\CronJob\Config\CronJob();
        }

        return $config;
    }
}