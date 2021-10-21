<?php namespace Daycry\CronJob\Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\CronJob\Scheduler;

class CronJob extends BaseConfig
{
	/**
	 * Directory
	 */
    public $FilePath = WRITEPATH . 'cronJob/';
    
	/**
	 * Filename setting
	 */
	public $FileName = 'jobs';

	/**
	 * Set true if you want save logs
	 */
	public $logPerformance = false;

	/*
    |--------------------------------------------------------------------------
    | Log Saving Method
    |--------------------------------------------------------------------------
    |
    | Set to specify the REST API requires to be logged in
    |
    | 'file'   Save in file
    | 'database'  Save in database
    |
    */
	public $logSavingMethod = 'file';

	/*
    |--------------------------------------------------------------------------
    | Database Group
    |--------------------------------------------------------------------------
    |
    | Connect to a database group for logging, etc.
    |
    */
	public $databaseGroup = 'default';

	/*
    |--------------------------------------------------------------------------
    | Cronjob Table Name
    |--------------------------------------------------------------------------
    |
    | The table name in your database that stores cronjobs
	|
	| Default table schema:
    |   CREATE TABLE `cronjob` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
			`type` VARCHAR(25) NOT NULL COLLATE 'utf8_general_ci',
			`action` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci',
			`environment` VARCHAR(100) NOT NULL COLLATE 'utf8_general_ci',
			`output` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
			`error` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
			`start_at` DATETIME NOT NULL,
			`end_at` DATETIME NOT NULL,
			`duration` TIME NOT NULL,
			`test_time` DATETIME NULL DEFAULT NULL,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`deleted_at` DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (`id`) USING BTREE,
			INDEX `deleted_at` (`deleted_at`) USING BTREE
		)
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB
		;
    |
    */
	public $tableName = 'cronjob';



    /*
    |--------------------------------------------------------------------------
	| Cronjobs
	|--------------------------------------------------------------------------
    |
	| Register any tasks within this method for the application.
	| Called by the TaskRunner.
	|
	| @param Scheduler $schedule
	*/
	public function init( Scheduler $schedule )
	{
		// $schedule->command('foo:bar')->everyMinute();

		// $schedule->shell('cp foo bar')->daily( '11:00 pm' );

		// $schedule->call( function() { do something.... } )->everyMonday()->named( 'foo' )
	}
}