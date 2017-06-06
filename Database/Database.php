<?php
namespace Database;

class Database extends Base
{
    protected $_type;
    protected $_options;

    public function init()
    {
        if (!$this->_type) {
            throw new Exception("Unvalid database type");
        }
        switch($this->_type){
            case "mysql":
            return new \Database\Connector\Mysql($this->_options);
            break;
            default:
            throw new \Exception("Unvalid database type");
            break;
        }
    }
}