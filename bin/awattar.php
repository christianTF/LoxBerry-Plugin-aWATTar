#!/usr/bin/php
<?php

require_once "loxberry_system.php";
require_once "loxberry_log.php";

$configfile = LBPCONFIGDIR . "/awattar.json";
$pricefile = "/tmp/awattar_pricedata.json";

$log = LBLog::newLog( [ "name" => "Price Processor" ] );
LOGSTART("Price processing");

$result_dataset = array();

// $countries = array ( "AT" => "",
					 // "DE" => "https://api.awattar.de/v1/marketdata"
// );


$cfg = json_decode(file_get_contents($configfile));

echo "Country: " . $cfg->general->country . "\n";

$pricing = get_pricing($cfg->general->url, $pricefile);
$pricing = prepare_prices($pricing);


price_stats($pricing);
calc_advises($pricing);

exit(0);

function get_pricing($dataurl, $pricefile)
{
	global $result_dataset;
	if( !file_exists($pricefile) or filemtime($pricefile) < time()-60*60 ) {
		echo "Getting data from API...\n";
		$starttime = strtotime( date('Y-m-d', time()) );
		$endtime = $starttime + 24*60*60;
		echo "Start-End: $starttime - $endtime\n";		
		$data = file_get_contents($dataurl."?start=".$starttime."000&end=".$endtime."000");
		file_put_contents( $pricefile, $data );
	} else {
		echo "Getting data from local file...\n";
		$data = file_get_contents($pricefile);
	}
	return json_decode($data);

}

function prepare_prices($pricing) 
{
	foreach ($pricing->data as $price) {
		$price->marketprice/=10;
	}
	return $pricing;
}

function price_stats($pricing)
{

	$currentprice = null;
	$lowestprice = null;
	$highestprice = null;
	$averageprice = null;
	$medianprice = null;
	$helper_total = null;
	$helper_count = 0;
	$currenthour_e = strtotime( date('Y-m-d H:00', time()) ) . "000";

	foreach ($pricing->data as $price) {

		if($currenthour_e == $price->start_timestamp) {
			$currentprice = $price->marketprice;
		}
		echo date('H:i', $price->start_timestamp/1000) . " " . $price->marketprice . "\n";
		
		// Save marketprice in an array to sort it later
		$price_arr[date('G', $price->start_timestamp/1000)] = $price->marketprice;
		
		// for average
		$helper_count++;
		$helper_total+=$price->marketprice;
		
		// for min/max
		if(is_null($lowestprice) || $price->marketprice < $lowestprice) {
			$lowestprice = $price->marketprice;
		}
		if(is_null($highestprice) || $price->marketprice > $highestprice) {
			$highestprice = $price->marketprice;
		}
	}

	sort($price_arr);

	echo "Current hour is  $currenthour_e\n";
	echo "Current price is $currentprice\n";
	$averageprice = $helper_total/$helper_count;
	echo "Average price is $averageprice\n";
	$medianprice = calculate_median($price_arr);
	echo "Median price is  $medianprice\n";
	echo "Lowest price is  $lowestprice\n";
	echo "Highest price is $highestprice\n";
	 
	// Price thresholds
	$threshold = 0;
	$price_ranking = null;
	foreach ($price_arr as $price) {
		echo "Threshold_" . sprintf("%02d", $threshold) . ": $price\n";
		$threshold++;
		if($price == $currentprice) {
			$price_ranking = $threshold;
		}
	}	
	echo "Current Price ranking: $price_ranking. lowest price\n";
}


function calc_advises($pricing) 
{
	global $cfg;
	$advises = array ();
	//echo var_dump($cfg->adviser) . "\n";
	//echo "Waschmaschine: " . $cfg->adviser->Waschmaschine->duration . "\n";
	//echo $cfg->adviser . "\n";
	
	foreach($cfg->adviser as $adv_name => $adv_obj) {
		echo "Advise Settings for " . $adv_name . "\n";
		calc_advice($pricing, $adv_name, $adv_obj);
	}
	
}	

function calc_advice($pricing, $adv_name, $adv_obj) 
{
	global $cfg;
	
	$dur = $adv_obj->duration;
	
	$weekday = date('w');
	$excludes = $adv_obj->excludes->$weekday;
	echo "Today is $weekday. Todays excludes: " . join(" ", $excludes) . "\n";
	
	echo "Elements in price list: " . count((array)$pricing->data) . "\n";
	
	$adv_lowest = null;
	$adv_lowest = array ();
	
	for ($i = 0; $i < count((array)$pricing->data); $i++) {
		$priceobj = $pricing->data[$i];
		$price_hour = date('H', $priceobj->start_timestamp/1000);
		if( in_array($price_hour, $excludes) ) {
			// This hour is excluded
			// echo "Hour $price_hour is excluded=deleted\n";
			unset($adv_lowest[$price_hour]);
			continue;
		}
		// echo "Calc avg $price_hour\n";
		// Look into the future
		$avg = null;
		for ($j = $i; $j < ($i+$dur); $j++) {
			
			if( isset($pricing->data[$j]) ) {
				$future_hour = date('H', $pricing->data[$j]->start_timestamp/1000);
				if( in_array($future_hour, $excludes) ) {
					unset($adv_lowest[$price_hour]);
					$avg = null;
					break;
				}
				$avg += $pricing->data[$j]->marketprice;
			} else {
				unset($adv_lowest[$price_hour]);
				$avg = null;
				break;
			}
		}
		if (!is_null($avg)) {
			$avg = $avg / $dur;
			$adv_lowest[$price_hour] = $avg;
		} else {
			unset($adv_lowest[$price_hour]);
		}
	}
	
	foreach($adv_lowest as $hour => $average) {
		echo "Avg. price for $dur hours at $hour: " . $average . "\n";
	}
	
	asort($adv_lowest);
	echo "Cheapest hour    : " . key($adv_lowest) . "\n";
	echo "Cheapest in      : " . (key($adv_lowest)-date('H')) . " hours\n";
	arsort($adv_lowest);
	echo "Expensive hour: " . key($adv_lowest) . "\n";
	echo "Expensive in     : " . (key($adv_lowest)-date('H')) . " hours\n";
	
		
}


function calculate_median($arr) {
    $count = count($arr); //total numbers in array
    $middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
    if($count % 2) { // odd number, middle is the median
        $median = $arr[$middleval];
    } else { // even number, calculate avg of 2 medians
        $low = $arr[$middleval];
        $high = $arr[$middleval+1];
        $median = (($low+$high)/2);
    }
    return $median;
}	
	

