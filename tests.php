<?php
require_once "DBD_autoloader.php";
use Database\Test as Test;

$database = new Database\Database(array(
    "_type"=>"mysql",
    "_options"=>array(
        "_host"=>"localhost",
        "_username"=>"root",
        "_password"=>"",
        "_schema"=>"test",
        "_port"=>"3306"
    )
));
$database = $database->init()->connect();
$id = $database->query()
->from("test_table", array("phone"))
->where("id = ?", "0")
->all();
Test::add(function() use ($database){
   return ($database instanceof Database\Database);
},"Inicjacja","DB");

Test::run();