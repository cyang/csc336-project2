<?php
require "config.php";

ini_set('memory_limit','4000M');
ini_set('max_execution_time', 6000);
ob_implicit_flush(true);
$starttime = microtime(true);

// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Strategy 1: Buy and hold selectively
echo "1. Calculate Averages, time: ".(microtime(true) - $starttime)." <br><br>";
echo "2. Running db query, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
$q_1 = "SELECT INSTRUMENT_ID, TRADE_DATE, CLOSE_PRICE FROM STOCK_HISTORY order by TRADE_DATE";
$r_1 = $db->query($q_1);

$stockArray = array();
echo "3. Processing query results into array, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
if ($r_1->num_rows > 0) {
        while($row = $r_1->fetch_assoc()) {
                if(array_key_exists($row["INSTRUMENT_ID"], $stockArray)){
                         $stockArray[$row["INSTRUMENT_ID"]][count($stockArray[$row["INSTRUMENT_ID"]])] = $row["CLOSE_PRICE"];
                }else{
                        $stockArray[$row["INSTRUMENT_ID"]] = array($row["CLOSE_PRICE"]);
}}} else {
        echo "0 results";
}

$cashStart = 10000.0;
$cash = $cashStart;

echo "4. Starting budget: ".$cash." time: ".(microtime(true) - $starttime)." <br><br>";
echo "5. Calculating 50 and 200 day averages, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
$avgArray = array();
for($i = 0; $i < count($stockArray); $i++){
  $priceArray = $stockArray[$i];
  $avgInstArray = array();
  for($day = 201; $day < count($priceArray); $day++){
    $day50 = array_sum(array_slice ( $priceArray, $day-51, 50 ))/50;
    $day200 = array_sum(array_slice ( $priceArray, $day-201, 200 ))/200;
    $avgInstArray[$day] = array(($day50 > $day200 ? 1:0), $priceArray[$day]);
    }
  $avgArray[$i] = $avgInstArray;
}

echo "6. Adding stocks to buy/sell list for each day, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
$buy = array();
$sell = array();
for($day = 201; $day < count($avgArray[0]); $day++){
  $buyDay = array();
  $sellDay = array();
  foreach($avgArray as $i => $arr){
    if($avgArray[$i][$day][0] == 1){
      $buyDay[$i]=$avgArray[$i][$day][1];
  }else{
      $sellDay[$i]=$avgArray[$i][$day][1];
  }}
  $buy[$day] =  $buyDay;
  $sell[$day] =  $sellDay;
}

$holdings = array();
echo "7. Running algorith,<br> for each day sell all stocks that match criteria and then buy randomly from the pool of stocks that meet buy criteria, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
for($day = 201; $day < count($avgArray[0]); $day++){
  foreach($sell[$day] as $i => $price){
    if(array_key_exists($i, $holdings)){
      $cash += $price * $holdings[$i];
      unset($holdings[$i]);
  }}

  while($cash > 250 and count($buy[$day]) > 0 ){
    $i = array_rand($buy[$day]);
    $cash -= $buy[$day][$i];
    if(array_key_exists($i, $holdings)){
      $holdings[$i] += 1;
    }else{
      $holdings[$i] = 1;
}}}

echo "8. Calculating profits, time: ".(microtime(true) - $starttime)." <br><br>";
flush();
ob_flush();
foreach($holdings as $i => $qty){
  $cash += end($stockArray[$i]) * $qty;
}
$profit = $cash - $cashStart;
echo "9. Profit: " . $profit . " time: ".(microtime(true) - $starttime)." <br><br>";
?>
