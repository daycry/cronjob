<?php
namespace Daycry\CronJob\Models;

use CodeIgniter\Model;

class CronJobLogModel extends Model
{
    protected $DBGroup = 'api';

    protected $table      = 'cronjob';

    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'object';

    protected $useSoftDeletes = true;

    protected $allowedFields = [ 'name', 'type', 'action', 'environment', 'output', 'error', 'start_at', 'end_at', 'duration', 'test_time' ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;

    public function setTableName( $tableName )
    {
        $this->table = $tableName;
    }

    public function setDBGroup( $DBGroup )
    {
        $this->DBGroup = $DBGroup;
    }
}