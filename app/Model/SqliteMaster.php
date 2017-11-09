<?php

namespace Model;

class SqliteMaster extends \Parable\ORM\Model
{
    /** @var string */
    protected $tableName = 'sqlite_master';
    protected $tableKey  = null;

    public $type;
    public $name;
    public $tbl_name;
    public $rootpage;
    public $sql;

    /**
     * Overriding Save function to prevent saving to system table.
     *
     * @return bool
     */
    public function save()
    {
        return false;
    }

}
