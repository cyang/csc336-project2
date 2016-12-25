<?php
require "config.php";

ini_set('memory_limit','2000M');
ini_set('max_execution_time', 3600);

// Connection
$db = new mysqli($host, $username, $password, "stockmarket");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Strategy 1: Buy and hold selectively
echo "1. Calculate Averages<br><br>";
$q_1 = "SELECT INSTRUMENT_ID, TRADE_DATE, CLOSE_PRICE FROM STOCK_HISTORY order by TRADE_DATE";
$r_1 = $db->query($q_1);

$stockArray = array();

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
echo "Starting budget: ". $cash . "<br><br>";

$avgArray = array();
for($i = 0; $i < count($stockArray); $i++){
  $priceArray = $stockArray[$i];
  $avgInstArray = array();
  for($day = 201; $day < count($priceArray); $day++){
    $day50 = array_sum(array_slice ( $priceArray, $day-51, 50 ))/50;
    $day200 = array_sum(array_slice ( $priceArray, $day-201, 200 ))/200;
//    echo("inst: $i day: $day 50: $day50 200: $day200<br>");
  if($day50 > $day200){
      $avgInstArray[$day] = array(1, $priceArray[$day]);
    }else {
      $avgInstArray[$day] = array(0, $priceArray[$day]);
    }}
  $avgArray[$i] = $avgInstArray;
}

$minDay = array();
for($day = 0; $day < count($avgArray[0]); $day++){
  $dayPrice = array();
  for($i = 0; $i < count($avgArray); $i++){
    if($avgArray[$i][$day][0] == 1){
       array_push($dayPrice,$avgArray[$i][$day][1]);
  }}
  array_push($minDay, min($dayPrice));
}

$buy = array();
$sell = array();
for($day = 0; $day < count($avgArray[0]); $day++){
  $buyDay = array();
  $sellDay = array();
  for($i = 0; $i < count($avgArray); $i++){
    if($avgArray[$i][$day][0] == 1){
      array_push($buyDay,array($i, $avgArray[$i][$day][1]));
  }else{
      array_push($sellDay,array($i, $avgArray[$i][$day][1]));
  }}
  array_push($buy, $buyDay);
  array_push($sell, $sellDay);
}

$holdings = array();

for($day = 0; $day < count($avgArray[0]); $day++){
  for($stock = 0; $stock < count($sell[$day]); $stock++){
    if(in_array($sell[$day][$stock][0], array_column($holdings,0))){
      $qtIndex = array_search($sell[$day][$stock][0], array_column($holdings,0));
      $cash += $sell[$day][$stock][1] * $holdings[$qtIndex][1];
    }
  }
while($cash > $min){
  $stock = array_rand($buy[$day]);
  $cash -= $buy[$day][$stock[1]];
  if(array_key_exists($stock[0], $holdings)){
    $holdings[$stock[0]][1] += 1;
  }else {
    $holdings[$stock[0]][1] = 1;
  }
}
}
$lastDay =  count($avgArray[0]) - 1;
  for($stock = 0; $stock < count($sell[$lastDay]); $stock++){
      $qtIndex = array_search($holdings[$stock][0], array_column($stockArray,0));
      $cash += $holdings[$stock][1] * $stockArray[$qtIndex][$lastDay];
  }
$profit = $cash - $cashStart;
echo "Profit: " . $profit;
?>
