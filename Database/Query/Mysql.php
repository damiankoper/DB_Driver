<?php
namespace Database\Query;

class Mysql extends \Database\Base
{
    protected $_connector;
    protected $_from;
    protected $_fields;
    protected $_limit;
    protected $_offset;
    protected $_order;
    protected $_direction;
    protected $_like;
    protected $_join = array();
    protected $_where = array();

    protected function _quote($value)
    {
        if (\is_string($value)) {
            $escaped = $this->_connector->escape($value);
            return "'{$escaped}'";
        }
        if (\is_array($value)) {
            $buffer = array();
            foreach ($value as $i) {
                \array_push($buffer, $this->_quote($i));
            }
            $buffer = \implode(", ", $buffer);
            return "({$buffer})";
        }
        if (\is_null($value)) {
            return "NULL";
        }
        if (\is_bool($value)) {
            return (int) $value;
        }
        return $this->_connector->escape($value);
    }
    public function from($from, $fields = array("*"))
    {
        if (empty($from)) {
            throw new \Exception("From not valid");
        }
        $this->_from = $from;
        if ($fields) {
            $this->_fields[$from] = $fields;
        }
        return $this;
    }
    public function join($join, $on, $fields = array(), $type = "INNER")
    {
        if(\array_search($type,array("INNER","OUTER","LEFT","RIGHT"))===false){
            throw new \Exception("Join not valid");
        }
        if (empty($join)) {
            throw new \Exception("Join not valid");
        }
        if (empty($on)) {
            throw new \Exception("Join not valid");
        }
        $this->_fields += array($join => $fields);
        $this->_join[] = "{$type} JOIN {$join} ON {$on}";
        return $this;
    }
    public function limit($limit, $page = 1)
    {
        if (empty($limit)) {
            throw new \Exception("Limit not valid");
        }
        $this->_limit = $limit;
        $this->_offset = $limit*($page-1);
        return $this;
    }
    public function order($order, $direction = "ASC")
    {
        if (empty($order)) {
            throw new \Exception("Order not valid");
        }
        $this->_order = $order;
        $this->_direction = $direction;
        return $this;
    }
   // public function
    public function where()
    {
        $arguments = \func_get_args();
        if (sizeof($arguments)<1) {
            throw new \Exception("Where not valid");
        }
        $arguments[0] = \preg_replace("#\?#", "%s", $arguments[0]);
        foreach (\array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $this->_quote($arguments[$i]);
        }
        $this->_where[] = \call_user_func_array("sprintf", $arguments);
        return $this;
    }
    protected function _buildSelect()
    {
        $fields = array();
        $where = $limit = $order = $join = "";
        $template = "SELECT %s FROM `%s` %s %s %s %s";

        foreach ($this->_fields as $table => $_fields) {
            foreach ($_fields as $field => $alias) {
                if (\is_string($field)) {
                    $fields[]="{$field} AS {$alias}";
                } else {
                    $fields[]=$alias;
                }
            }
        }
        $fields = \implode(", ", $fields);

        $_join = $this->_join;
        if (!empty($_join)) {
            $join = \implode(" ", $_join);
        }
        $_where = $this->_where;
        if (!empty($_where)) {
            $joined = \implode(" AND ", $_where);
            $where = "WHERE {$joined}";
        }
        $_order = $this->_order;
        if (!empty($_order)) {
            $_direction = $this->_direction;
            $order = "ORDER BY {$_order} {$_direction}";
        }
        $_limit = $this->_limit;
        if (!empty($_limit)) {
            $_offset = $this->_offset;
            if ($_offset) {
                $limit = "LIMIT {$_limit}, {$_offset}";
            } else {
                $limit = "LIMIT {$_limit}";
            }
        }

        return sprintf($template, $fields, $this->_from, $join, $where, $order, $limit);
    }

    protected function _buildInsert($data)
    {
        $fields = array();
        $values = array();
        $template = "INSERT INTO `%s` (`%s`) VALUES (%s)";
        foreach ($data as $field => $value) {
            $fields[]=$field;
            $values[]=$this->_quote($value);
        }

        $fields = \implode("`, `", $fields);
        $values = \implode(", ", $values);
        return \sprintf($template, $this->_from, $fields, $values);
    }

    protected function _buildUpdate($data)
    {
        $parts = array();
        $where = $limit = "";
        $template = "UPDATE `%s` SET %s %s %s";

        foreach ($data as $field=>$value) {
            $parts[]="{$field} = ".$this->_quote($value);
        }
        $parts = \implode(", ", $parts);

        $_where = $this->_where;
        if (!empty($_where)) {
            $joined = \implode(" AND ", $_where);
            $where = "WHERE {$joined}";
        }
        $_limit = $this->_limit;
        if (!empty($_limit)) {
            $_offset = $this->_offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }
        return \sprintf($template, $this->_from, $parts, $where, $limit);
    }
    protected function _buildDelete()
    {
        $where = $limit = "";
        $template = "DELETE FROM %s %s %s";

        $_where = $this->_where;
        if (!empty($_where)) {
            $joined = \implode(" AND ", $_where);
            $where = "WHERE {$joined}";
        }
        $_limit = $this->_limit;
        if (!empty($_limit)) {
            $_offset = $this->_offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }
        return \sprintf($template, $this->_from, $where, $limit);
    }
    public function save($data)
    {
        $isInsert = sizeof($this->_where) == 0;
        if ($isInsert) {
            $sql = $this->_buildInsert($data);
        } else {
            $sql = $this->_buildUpdate($data);
        }
        $result = $this->_connector->execute($sql);

        if ($result===false) {
            throw new \Exception("SQL error");
        }
        if ($isInsert) {
            return $this->_connector->getLastInsertId();
        }
        return 0;
    }
    public function delete()
    {
        $sql = $this->_buildDelete();
        $result = $this->_connector->execute($sql);
        if ($result===false) {
            throw new \Exception("SQL error");
        }
        return $this->_connector->getAffectedRows();
    }
    public function first()
    {
        $limit = $this->_limit;
        $offset = $this->_offset;

        $this->limit(1);
        $all = $this->all();
        $first = reset($all);
        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }
        return $first;
    }
    public function count(){
        $limit = $this->_limit;
        $offset = $this->_offset;
        $fields = $this->_fields;
        $this->_fields = array($this->_from => array("COUNT(1)" => "rows"));
        $this->limit(1);
        $row = $this->first();
        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }
        if ($fields) {
            $this->_fields = $fields;
        }
        return $row["rows"];
    }
    public function all(){
        $sql = $this->_buildSelect();
        $result = $this->_connector->execute($sql);
        if($result===false){
            $error = $this->_connector->getLastError();
            throw new \Exception("SQL error: {$error}");
        }
        $rows = array();
        for($i=0;$i<$result->num_rows;$i++){
            $rows[]=$result->fetch_array(\MYSQLI_ASSOC);
        }
        return $rows;
    }
}