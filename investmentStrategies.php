<?php
require "config.php";
// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
} 
$STOCK_HISTORY = "SELECT * FROM STOCK_HISTORY";

$r = $db->query($STOCK_HISTORY);
if ($r->num_rows > 0) {
	$row = $r->fetch_assoc();
	echo $row["TRADE_DATE"];
} else {
	echo "0 results";
}

?>
