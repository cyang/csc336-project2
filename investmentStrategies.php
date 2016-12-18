<?php
require "config.php";
// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Strategy 1: Buy and hold selectively 
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
		$stockArray[$row["INSTRUMENT_ID"]] = array($row["startPrice"], $row["endPrice"], false);
	}
} else {
	echo "0 results";
}

$budget = 10000.0;
echo "Starting budget: ". $budget . "<br><br>";
$profit = 0.0;
$stocksLooked = 0;

echo "Purchased stocks: <br>";
while ($budget > 0 and $stocksLooked <= 1000) {
	$rand = rand(0, 999);
	if($stockArray[$rand][2] == false) {
		// Subtract from budget and calculate profit
		$stockArray[$rand][2] = true;
		$stocksLooked += 1;
 
		if ($budget - $stockArray[$rand][0] >=  0) {
			$budget -= $stockArray[$rand][0];
		} else { continue; }

		$profit += $stockArray[$rand][1] - $stockArray[$rand][0];
		echo "INSTRUMENT_ID: " . $rand . " | PRICE: " . $stockArray[$rand][0] . "<br>";
	}
}

echo "<br>Budget left over: " . $budget . "<br>"; 
echo "Profit: " . $profit;
?>
