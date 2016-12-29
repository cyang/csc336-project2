<?php
require "config.php";

// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
	die("Connection failed: " . $db->connect_error);
}

// ob_start(); // begin collecting output
// include 'movingAverages.php';
// $result = ob_get_clean();
// echo $result; //results of movingAverages.php will be stored in result

echo "1. Momentum- Buy/Sell when the stock has gone up/down for some period of time. <br><br>";

define("CONSTANT_BUDGET", 10000.0);
// Check for opportunities to buy if the shorter day moving average is above the longer day moving average
ini_set('memory_limit','4000M');
ini_set('max_execution_time', 6000);
$starttime = microtime(true);

echo "Processing query results into array, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();

$q_1 = "SELECT INSTRUMENT_ID, TRADE_DATE, OPEN_PRICE FROM STOCK_HISTORY order by TRADE_DATE";
$r_1 = $db->query($q_1);

$stockArray = array();

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

$budget = CONSTANT_BUDGET;
echo "Starting budget: ". $budget . "<br><br>";
$profit = 0.0;
$buy = array();
$sell = array();
$holdings = array();
// for every 10 days, calculate percent change in open price and buy or sell
for($day = 0; $day < count($stockArray); $day++){
  $priceArray = $stockArray[$day];
  //print_r(array_values($priceArray));
  if($day % 30 == 0){
    //percentage decrease
    if($priceArray[$day] > $priceArray[$day+30]){
      $decrease = $priceArray[$day] - $priceArray[$day+30];
      $per_decrease = $decrease / $priceArray[$day] * 100;
      if ($per_decrease > 3.0 && $budget > 0){
        //sell
        array_push($sell,$per_decrease);
        $sold += $priceArray[$day];
        echo "Percentage decrease, We should SELL: -$per_decrease% \n";
      }
    }
    elseif ($priceArray[$day] == $priceArray[$day+30]) {
      echo "No percent change, Do Nothing \n";
    }
    else{
      $increase = $priceArray[$day+30] - $priceArray[$day];
      $per_increase = $increase / $priceArray[$day] * 100;
      echo "Percentage Increase, We should BUY: +$per_increase% \n";
      if($per_increase > 3.0 ){
        //buy
        array_push($buy, $per_increase);
        $bought += $priceArray[$day];
        echo "Percentage Increase, We should BUY: +$per_increase% \n";

      }
    }
  }
}
$profit = $bought - $sold;
echo " <br>";
echo "Total Stocks Sold " . count($sell). "<br><br>";
echo "Total Stocks Bought ". count($buy). "<br><br>";
echo "Profit" . $profit;
?>
