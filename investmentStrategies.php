<?php
require "config.php";
// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
} 
$STOCK = "SET @STOCK = (SELECT * FROM STOCK)";
$db->query($q_1);

echo $db->query("SELECT @STOCK LIMIT 1);

?>
