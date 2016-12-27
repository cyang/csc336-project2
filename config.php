<?php
$host = "134.74.126.104";
$username = "USERNAME";
$password = "PASSWORD";
$team_database = "F16336team5";
// Connection
$db = new mysqli($host, $username, $password, $team_database);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>
