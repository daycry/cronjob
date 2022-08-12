<?php

namespace Daycry\CronJob\Models;

use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;
use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

class CronJobLogModel extends Model
{
    protected $DBGroup            = 'default';
    protected $table              = 'cronjob';
    protected $primaryKey         = 'id';
    protected $useAutoIncrement   = true;
    protected $returnType         = 'object';
    protected $useSoftDeletes     = false;
    protected $allowedFields      = ['name', 'type', 'action', 'environment', 'output', 'error', 'start_at', 'end_at', 'duration', 'test_time'];
    protected $useTimestamps      = true;
    protected $createdField       = 'created_at';
    protected $updatedField       = 'updated_at';
    protected $deletedField       = 'deleted_at';
    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    public function __construct(?ConnectionInterface &$db = null, ?ValidationInterface $validation = null)
    {
        if ($db === null) {
            $db = Database::connect(config('CronJob')->databaseGroup);
            $this->DBGroup = config('CronJob')->databaseGroup;
        }

        $this->table = config('CronJob')->tableName;

        parent::__construct($db, $validation);
    }
}
