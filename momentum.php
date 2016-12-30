<?php
require "config.php";

// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
	die("Connection failed: " . $db->connect_error);
}

echo "Momentum - Buy/Sell when the stock has gone up/down for some period of time. <br><br>";

define("CONSTANT_BUDGET", 10000.0);
define("CONSTANT_BUY_THRESHOLD_PERCENTAGE", 3.0);
define("CONSTANT_SELL_THRESHOLD_PERCENTAGE", 3.0);
define("CONSTANT_PERIOD", 30);

$cash = CONSTANT_BUDGET;
echo "Sell Threshold: ". CONSTANT_SELL_THRESHOLD_PERCENTAGE . "%<br>";
echo "Buy Threshold: ". CONSTANT_BUY_THRESHOLD_PERCENTAGE . "%<br><br>";

ini_set('memory_limit','4000M');
ini_set('max_execution_time', 6000);
$starttime = microtime(true);

echo "1. Calculate momentum, time: ".(microtime(true) - $starttime)." <br><br>";
echo "2. Running db query, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();

$q_1 = "SELECT INSTRUMENT_ID, TRADE_DATE, OPEN_PRICE FROM STOCK_HISTORY order by TRADE_DATE";
$r_1 = $db->query($q_1);

$stockArray = array();

echo "3. Processing query results into array, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();


// Using a 2D array we store INSTRUMENT_ID as key, and the corresponding OPEN_PRICE as values in an array ordered by TRADE_DATE
if ($r_1->num_rows > 0) {
	while($row = $r_1->fetch_assoc()) {
		if(array_key_exists($row["INSTRUMENT_ID"], $stockArray)){
			array_push($stockArray[$row["INSTRUMENT_ID"]], $row["OPEN_PRICE"]);
		}else{
			$stockArray[$row["INSTRUMENT_ID"]] = array($row["OPEN_PRICE"]);
		}
	}
} else {
	echo "0 results";
}

echo "4. Starting budget: $".$cash." time: ".(microtime(true) - $starttime)." <br><br>";
echo "5. Calculating momentum, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();

$momentumArray = array();

for($i = 0; $i < count($stockArray); $i++){
	$priceArray = $stockArray[$i];
	$momentumInstArray = array();

	// Check for opportunities to buy if there is a percentage increase, and sell if there is a percentage decrease 

	// for every CONSTANT_PERIOD days, calculate percent change in open price and buy or sell
	for($day = CONSTANT_PERIOD; $day < count($priceArray); $day+=CONSTANT_PERIOD){

		//inital value for not buying/selling
		$momentumInstArray[$day] = array(2);

		// make sure the initial price is not zero!
		if($priceArray[$day-CONSTANT_PERIOD] != 0) {
			if($priceArray[$day] > $priceArray[$day-CONSTANT_PERIOD]){
				$per_increase = ($priceArray[$day] - $priceArray[$day-CONSTANT_PERIOD]) / $priceArray[$day-CONSTANT_PERIOD] * 100;
				if ($per_increase > CONSTANT_SELL_THRESHOLD_PERCENTAGE){
					//buy
					$momentumInstArray[$day] = array(1, $priceArray[$day]);
				}
			} elseif($priceArray[$day] < $priceArray[$day-CONSTANT_PERIOD]) {
				$per_decrease = ($priceArray[$day] - $priceArray[$day-CONSTANT_PERIOD]) / $priceArray[$day-CONSTANT_PERIOD] * -100;
				if($per_decrease > CONSTANT_BUY_THRESHOLD_PERCENTAGE){
					//sell
					$momentumInstArray[$day] = array(0, $priceArray[$day]);
				}
			}
		}
	}
	$momentumArray[$i] = $momentumInstArray;
}

echo "6. Adding stocks to buy/sell list for each day, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();

$buy = array();
$sell = array();
for($day=CONSTANT_PERIOD; $day < count($momentumArray[0]); $day+=CONSTANT_PERIOD) {
	$buyDay = array();
	$sellDay = array();

	foreach($momentumArray as $i => $arr) {
		if($momentumArray[$i][$day][0] == 1) {
			$buyDay[$i]=$momentumArray[$i][$day][1];	
		} elseif ($momentumArray[$i][$day][0] == 0) {
			$sellDay[$i]=$momentumArray[$i][$day][0];
		}
	}
	
	$buy[$day] = $buyDay;
	$sell[$day] = $sellDay;
} 


$holdings = array();
echo "7. Running algorithm:<br> for each day sell all stocks that match criteria and then buy randomly from the pool of stocks that meet buy criteria, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();


for($day = CONSTANT_PERIOD; $day < count($momentumArray[0]); $day+=CONSTANT_PERIOD) {
	// Sell holdings if own
	foreach($sell[$day] as $i => $price) {
		if (array_key_exists($i, $holdings)) {
			$cash += $price * $holdings[$i];
			unset($holdings[$i]);
			echo 'Instrument_ID Sold ' .$i. ' | Open Price '. $stockArray[$i][0]. ' | Closing Price '. $stockArray[$i][1] . '<br>';
		}
	}
	
	// Randomly buy a stock
	while($cash > 250 and count($buy[$day]) > 0 ){
		$i = array_rand($buy[$day]);
		$cash -= $buy[$day][$i];
		if(array_key_exists($i, $holdings)){
			$holdings[$i] += 1;
		}else{
			$holdings[$i] = 1;
		}
		echo 'Instrument ID Bought: '.$i. ' | Open Price ' . $stockArray[$i][0]. ' | Closing Price '. $stockArray[$i][1]. '<br>';
	}
}


echo "8. Calculating profits, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
foreach($holdings as $i => $qty){
	$cash += end($stockArray[$i]) * $qty;
}

$profit = $cash - CONSTANT_BUDGET;
echo "9. Profit: $" . $profit . " time: ".(microtime(true) - $starttime)." <br><br>";
?>
