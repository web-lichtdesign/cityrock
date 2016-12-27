<?php
include_once ("_init.php");

echo "Start skript \n";
$db = Database::createConnection();
$result = $db->query("select * from user");
while($row=$result->fetch_assoc()){
    debug($row);
}
/**
 * Created by PhpStorm.
 * User: easy
 * Date: 25.11.16
 * Time: 23:14
 */