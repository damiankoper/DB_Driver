<?php
require_once "DBD_autoloader.php";
use Database\Test as Test;

$options = array(
    "_type"=>"mysql",
    "_options"=>array(
        "_host"=>"localhost",
        "_username"=>"root",
        "_password"=>"",
        "_schema"=>"test",
        "_port"=>"3306"
    )
);
Test::add(function () use ($options) {
    $database = new Database\Database();
    return ($database instanceof Database\Database);
}, "Init", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init();
    return ($database instanceof Database\Connector\Mysql);
}, "Connector init", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    return ($database instanceof Database\Connector\Mysql);
}, "Connect", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $database = $database->disconnect();
    try {
        $database->execute("SELECT 1");
    } catch (Exception $e) {
        return ($database instanceof Database\Connector\Mysql);
    }
    return false;
}, "Disconnect", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $escaped = $database->escape("'foo".'bar"``@');
    return $escaped == "\'foo".'bar\"``@';
}, "Escape", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $database->execute("xD");
    return (bool)$database->getLastError();
}, "Last Error", "DB");

Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $database->execute("DROP TABLE IF EXISTS `create_test`");
    $database->execute("create table `create_test` (
            `id` INT,
            PRIMARY KEY (`id`),
            `first_name` VARCHAR(50),
            `last_name` VARCHAR(50),
            `email` VARCHAR(50),
            `gender` VARCHAR(50)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    return !$database->getLastError();
}, "SQL query", "DB");

Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $database->execute("UPDATE `select_test` SET `last_name`='Badur'");
    $database->execute("UPDATE `select_test` SET `last_name`='Koper'");
    return $database->getAffectedRows()==10;
}, "SQL query affected rows", "DB");

Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $database->execute("INSERT INTO `create_test` (`id`,`first_name`,`last_name`,`gender`) VALUES ('12','Damian','Koper','none')");
    $insert_id = $database->getLastInsertId();
    $database->execute("DELETE FROM `create_test` WHERE `gender` = 'none'");
    return $insert_id === 0;
}, "SQL query last insert id", "DB");

Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query();
    return ($rows instanceof Database\Query\Mysql);
}, "Query init", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query();
    return ($rows->getConnector() instanceof Database\Connector\Mysql);
}, "Query connector", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test")
        ->first();
    return $rows["id"] === "18975277";
}, "First", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test")
        ->all();
    return sizeof($rows)===10;
}, "All", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test")
        ->count();
    return $rows === "10";
}, "Count", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test")
        ->limit(1, 2)
        ->order("id", "desc")
        ->all();
    return $rows[0]["first_name"]==="Jeddy";
}, "Limit, order", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test")
        ->where("id = ? OR first_name = ?", "91172420", "Tara")
        ->all();
    return $rows[1]["first_name"]==="Jeddy" && sizeof($rows)===2;
}, "Where", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test", array("id"=>"ajdi"))
        ->all();
    return sizeof($rows)===10 && isset($rows[0]["ajdi"]) ;
}, "Aliases", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $database->query()
        ->from("create_test")
        ->save(array("id"=> 5));
    $row = $database->query()
        ->from("create_test")
        ->where("id=?", 5)
        ->all();
    return !empty($row);
}, "Save Insert", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $row = $database->query()
        ->from("create_test")
        ->where("id=?", 5)
        ->save(array("id"=> 6));
    $row = $database->query()
        ->from("create_test")
        ->where("id=?", 5)
        ->all();
    return empty($row);
}, "Save Update", "DB");
Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $row = $database->query()
        ->from("create_test")
        ->where("id=?", 6)
        ->delete();
    $row = $database->query()
        ->from("create_test")
        ->where("id=?", 6)
        ->all();
    return empty($row);
}, "Save Delete", "DB");

Test::add(function () use ($options) {
    $database = new Database\Database($options);
    $database = $database->init()->connect();
    $rows = $database->query()
        ->from("select_test")
        ->join("join_test","select_test.id = join_test.id",array("data"),"INNER")
        ->all();
    return sizeof($rows)===2;
}, "Join Inner", "DB");

Test::run();
