<?php
require "config.php";
// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

define("CONSTANT_BUDGET", 10000.0);

// Strategy 1: Buy and hold selectively 
echo "1. Buy and hold selectively<br><br>";
$q_1 = "SELECT startStock.INSTRUMENT_ID, startStock.TRADE_DATE as startDate,
startStock.OPEN_PRICE as startPrice, endStock.TRADE_DATE as endDate,
endStock.OPEN_PRICE as endPrice 
FROM (SELECT * FROM STOCK_HISTORY GROUP BY INSTRUMENT_ID ORDER BY TRADE_DATE)
as startStock 
LEFT JOIN
(SELECT temp.INSTRUMENT_ID, temp.TRADE_DATE, temp.OPEN_PRICE FROM (SELECT *
FROM `STOCK_HISTORY` ORDER BY TRADE_DATE DESC) as temp GROUP BY
temp.INSTRUMENT_ID ORDER BY temp.INSTRUMENT_ID) as endStock 
ON startStock.INSTRUMENT_ID=endStock.INSTRUMENT_ID
ORDER BY INSTRUMENT_ID;";
$r_1 = $db->query($q_1);

$stockArray = array();

if ($r_1->num_rows > 0) {
	while($row = $r_1->fetch_assoc()) {
		$stockArray[$row["INSTRUMENT_ID"]] = array($row["startPrice"], $row["endPrice"]);
	}
} else {
	echo "0 results";
}


$budget = CONSTANT_BUDGET;
echo "Starting budget: ". $budget . "<br><br>";
$profit = 0.0;

echo "Purchased stocks: <br>";
while ($budget > 0) {
	$rand = rand(0, 999);
	// Subtract from budget and calculate profit
	if ($budget - $stockArray[$rand][0] >=  0) {
		$budget -= $stockArray[$rand][0];
	} else { continue; }

	$profit += $stockArray[$rand][1] - $stockArray[$rand][0];
	echo "INSTRUMENT_ID: " . $rand . " | START_PRICE: " . $stockArray[$rand][0] . " | END_PRICE: " . $stockArray[$rand][1] . "<br>";
}

echo "<br>Budget left over: " . $budget . "<br>"; 
echo "Profit: " . $profit . "<br><br>";

// 3. Index investment
echo "3. Index investment:<br>";

// For the first index:
$index1TotalStart = 0.0;
$index1TotalEnd = 0.0;
for ($i = 0; $i < 500; $i++) {
	$index1TotalStart += $stockArray[$i][0];
	$index1TotalEnd += $stockArray[$i][1];	
}
$index1PercentChange = ($index1TotalStart-$index1TotalEnd)/$index1TotalStart;
echo "Index 1 percent change: " . $index1PercentChange . "<br>";
echo "Budget change: " . $index1PercentChange*CONSTANT_BUDGET . "<br><br>"; 

// For the second index:
$index2TotalStart = 0.0;
$index2TotalEnd = 0.0;
for ($i = 500; $i < 1000; $i++) {
	$index2TotalStart += $stockArray[$i][0];
	$index2TotalEnd += $stockArray[$i][1];	
}
$index2PercentChange = ($index2TotalStart-$index2TotalEnd)/$index2TotalStart;
echo "Index 2 percent change: " . $index2PercentChange . "<br>";
echo "Budget change: " . $index2PercentChange*CONSTANT_BUDGET . "<br>";?>
