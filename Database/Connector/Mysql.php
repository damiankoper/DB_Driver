<?php
namespace Database\Connector;

class Mysql extends \Database\Connector
{
    protected $_service;
    protected $_host;
    protected $_username;
    protected $_password;
    protected $_schema;
    protected $_port ="3306";
    protected $_charset = "utf8";
    protected $_engine = "MariaDB";
    protected $_isConnected = false;

    protected function _isValidService()
    {
        $isEmpty = empty($this->_service);
        $isInstance = $this->_service instanceof \MySQLi;
        if ($this->_isConnected && $isInstance && !$isEmpty) {
            return true;
        }
        return false;
    }
    public function connect()
    {
        if (!$this->_isValidService()) {
            $this->_service = new \MySQLi(
                $this->_host,
                $this->_username,
                $this->_password,
                $this->_schema,
                $this->_port
            );
            if ($this->_service->connect_error) {
                throw new \Exception("Cannot connect to service");
            }
            $this->_isConnected = true;
        }
        return $this;
    }
    public function disconnect()
    {
        if ($this->_isValidService()) {
            $this->_isConnected = false;
            $this->_service->close();
        }
        return $this;
    }
    public function query(){
        return new \Database\Query\Mysql(array(
            "_connector"=>$this
        ));
    }
    public function execute($sql){
        if(!$this->_isValidService()){
            throw new \Exception("Not connected to valid service");
        }
        return $this->_service->query($sql);
    }
    public function escape($value){
        if(!$this->_isValidService()){
            throw new \Exception("Not connected to valid service");
        }
        return $this->_service->real_escape_string($value);
    }
    public function getLastInsertId(){
        if(!$this->_isValidService()){
            throw new \Exception("Not connected to valid service");
        }
        return $this->_service->insert_id;
    }
    
    public function getAffectedRows(){
        if(!$this->_isValidService()){
            throw new \Exception("Not connected to valid service");
        }
        return $this->_service->affected_rows;
    }
    
    public function getLastError(){
        if(!$this->_isValidService()){
            throw new \Exception("Not connected to valid service");
        }
        return $this->_service->error;
    }
}
