#!/usr/bin/php
<?php

// Shutdown function
register_shutdown_function('shutdown');
function shutdown()
{
	global $log, $result_dataset, $resultfile;
	
	// Save data
	if( isset($result_dataset) && isset($resultfile) ) {
		LOGOK("Writing temporary result file to disk");
		file_put_contents($resultfile, json_encode($result_dataset));
	}
	if(isset($log)) {
		LOGEND("Processing finished");
	}
}

// Main program

require_once "loxberry_system.php";
require_once "loxberry_log.php";

$configfile = LBPCONFIGDIR . "/awattar.json";
$pricefile = "/tmp/awattar_pricedata.json";
$resultfile = "/tmp/awattar_resultdata.json";
$mqttconfigfile = LBPCONFIGDIR . "/mqtt.json";

$log = LBLog::newLog( [ "name" => "Price Processor", "loglevel"=> 7, "stderr" => 1 ] );
LOGSTART("Price processing");

// Commandline options
$longopts = array( "delay:" );
$options = getopt("", $longopts);
if(!empty($options['delay'])) {
	LOGINF("Startup delay of " . $options['delay'] . " seconds requested by option --delay (usually from cronjob)...");
	sleep($options['delay']);
	LOGOK("Continuing startup after delay.");
}

$result_dataset = array();

// $countries = array ( "AT" => "",
					 // "DE" => "https://api.awattar.de/v1/marketdata"
// );

LOGINF("Reading config file $configfile");
$cfg = json_decode(file_get_contents($configfile));

LOGINF("Country: " . $cfg->general->country);

$currenthour_e = strtotime( date('Y-m-d H:00', time()) ) . "000";

$pricing = get_pricing($cfg->general->url, $pricefile);
if(empty($pricing)) {
	LOGCRIT("Could not query valid price data");
	exit(1);
} else {
	LOGOK("Price data queried successfully");
}
$pricing = prepare_prices($pricing);


price_stats($pricing);
calc_advises($pricing);

mqtt_send();


exit(0);

function get_pricing($dataurl, $pricefile)
{
	global $result_dataset;
	
	$curr_date = date('Y-m-d', time());
	$modified = filemtime($pricefile);
	if(!empty($modified)) { 
		$modified_date = date('Y-m-d', $modified);
	}
		
	
	if( !file_exists($pricefile) or $curr_date != $modified_date ) {
		LOGINF("Getting data from API...");
		$starttime = strtotime( date('Y-m-d', time()) );
		$endtime = $starttime + 24*60*60;
		LOGDEB("Start-End: $starttime - $endtime");
		$data = file_get_contents($dataurl."?start=".$starttime."000&end=".$endtime."000");
		LOGDEB("Writing result to $pricefile");
		file_put_contents( $pricefile, $data );
	} else {
		LOGINF("Getting data from local file...");
		$data = file_get_contents($pricefile);
	}
	
	$result_dataset['date']['now'] = date('Y-m-d H:m', time());
	$result_dataset['date']['now_epoch'] = time();
	$result_dataset['date']['now_loxtime'] = epoch2lox();
	$filemtime = filemtime($pricefile);
	$result_dataset['date']['fetch'] = date('Y-m-d H:m', $filemtime);
	$result_dataset['date']['fetch_epoch'] = $filemtime;
	$result_dataset['date']['fetch_loxtime'] = epoch2lox($filemtime);
	$result_dataset['date']['weekday'] = date('w');
	
	
	return json_decode($data);

}

function prepare_prices($pricing) 
{
	global $cfg;
	
	$pricemodifier = $cfg->general->pricemodifier;
	if(!empty($pricemodifier)) {
		LOGOK("Price modifier $pricemodifier will be applied to prices");
		$pricemodifier = str_replace('p', '$p', $pricemodifier);
	} else {
		LOGINF("No price modifier set");
	}
	LOGDEB("Changing prices from EUR/MWh to cent/kWh");
	foreach ($pricing->data as $price) {
		$price->marketprice/=10;
		if(!empty($pricemodifier)) {
			$r = null;
			try {
				$p = $price->marketprice;
				eval( '$r = ' . $pricemodifier . ';' );
				LOGDEB("Old price: $p Modified price: $r");
			} catch (Exception $e) {
				LOGERR("Price modifier error: " . $e->getMessage());
			}
			if(isset($r)) {
				$price->marketprice = $r;
			}
		}
	}
	return $pricing;
}

function price_stats($pricing)
{
	global $result_dataset, $currenthour_e;
	
	$currentprice = null;
	$lowestprice = null;
	$highestprice = null;
	$averageprice = null;
	$medianprice = null;
	$helper_total = null;
	$helper_count = 0;

	foreach ($pricing->data as $price) {

		if($currenthour_e == $price->start_timestamp) {
			$currentprice = $price->marketprice;
		}
		LOGDEB(date('H:i', $price->start_timestamp/1000) . " " . $price->marketprice);
		$result_dataset['prices']['hour_'. date('H', $price->start_timestamp/1000) ] = $price->marketprice;
			
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

	LOGINF("Current hour is " . date('H', intval($currenthour_e/1000)) . " (epoch $currenthour_e)");
	LOGINF("Current price is $currentprice");
	$averageprice = $helper_total/$helper_count;
	LOGINF("Average price is $averageprice");
	$medianprice = calculate_median($price_arr);
	LOGINF("Median price is  $medianprice");
	LOGINF("Lowest price is  $lowestprice");
	LOGINF("Highest price is $highestprice");
 
	// Price thresholds
	$threshold = 0;
	$price_ranking = null;
	foreach ($price_arr as $price) {
		LOGDEB("Threshold_" . sprintf("%02d", $threshold) . ": $price");
		$result_dataset['threshold'][sprintf("%02d", $threshold)] = $price;
		$threshold++;
		if($price == $currentprice) {
			$price_ranking = $threshold;
		}
	}	
	LOGINF("Current Price ranking: $price_ranking. lowest price");

	$result_dataset['price']['hour'] = date('H', intval($currenthour_e/1000));
	$result_dataset['price']['current'] = $currentprice;
	$result_dataset['price']['average'] = $averageprice;
	$result_dataset['price']['median'] = $medianprice;
	$result_dataset['price']['low'] = $lowestprice;
	$result_dataset['price']['high'] = $highestprice;
	$result_dataset['price']['ranking'] = $price_ranking;

}


function calc_advises($pricing) 
{
	global $cfg;
	$advises = array ();
	//echo var_dump($cfg->adviser) . "\n";
	//echo "Waschmaschine: " . $cfg->adviser->Waschmaschine->duration . "\n";
	//echo $cfg->adviser . "\n";
	
	foreach($cfg->adviser as $adv_name => $adv_obj) {
		LOGINF("Advise Settings for " . $adv_name);
		calc_advice($pricing, $adv_name, $adv_obj);
	}
	
}	

function calc_advice($pricing, $adv_name, $adv_obj) 
{
	global $cfg, $result_dataset, $currenthour_e;
	
	$currenthour = date('H', intval($currenthour_e/1000));
	
	$dur = $adv_obj->duration;
	
	$result_dataset['advise'][$adv_name]['device'] = $adv_name;
	$result_dataset['advise'][$adv_name]['duration'] = $dur;
	
	$weekday = date('w');
	$excludes = $adv_obj->excludes->$weekday;
	LOGINF("Today is $weekday. Todays excludes: " . join(" ", $excludes));
	
	LOGDEB("Elements in price list: " . count((array)$pricing->data));
	
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
		LOGDEB("Avg. price for $dur hours at $hour: " . $average);
	}
	
	asort($adv_lowest);
	LOGINF("Cheapest hour      : " . key($adv_lowest));
	LOGINF("Cheapest in        : " . (key($adv_lowest)-date('H')) . " hours");
	LOGINF("Cheapest avg price : " . $adv_lowest[key($adv_lowest)]);
	
	
	$result_dataset['advise'][$adv_name]['low_hour'] = key($adv_lowest);
	$result_dataset['advise'][$adv_name]['low_in'] = (key($adv_lowest)-date('H'));
	$result_dataset['advise'][$adv_name]['low_price_avg'] = $adv_lowest[key($adv_lowest)];
	echo "DEBUG: $currenthour // " . key($adv_lowest) . "\n";
	if( $currenthour >= key($adv_lowest) and $currenthour <= (key($adv_lowest)+$dur-1) ) {
		LOGINF("Cheapest phase: CURRENTLY CHEAPEST");
		$result_dataset['advise'][$adv_name]['low_price_active'] = "1";
	} else {
		LOGINF("Cheapest phase: Not in cheapest phase");
		$result_dataset['advise'][$adv_name]['low_price_active'] = "0";
	}
	
	arsort($adv_lowest);
	LOGINF("Expensive hour: " . key($adv_lowest));
	LOGINF("Expensive in     : " . (key($adv_lowest)-date('H')) . " hours");
	LOGINF("Expensive avg price : " . $adv_lowest[key($adv_lowest)]);
	$result_dataset['advise'][$adv_name]['high_hour'] = key($adv_lowest);
	$result_dataset['advise'][$adv_name]['high_in'] = (key($adv_lowest)-date('H'));
	$result_dataset['advise'][$adv_name]['high_price_avg'] = $adv_lowest[key($adv_lowest)];
	if( $currenthour >= key($adv_lowest) and $currenthour <= (key($adv_lowest)+$dur-1) ) {
		LOGINF("Expensive phase: CURRENTLY EXPENSIVE");
		$result_dataset['advise'][$adv_name]['high_price_active'] = "1";
	} else {
		LOGINF("Expensive phase: Not in most expensive phase");
		$result_dataset['advise'][$adv_name]['high_price_active'] = "0";
	}
	
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
	
function mqtt_send()
{
	global $mqttconfigfile, $result_dataset;
	
	require_once "loxberry_io.php";
	require_once "phpMQTT/phpMQTT.php";
	
	LOGINF("Reading MQTT config from plugin");
	
	$mqttcfg = @json_decode(file_get_contents($mqttconfigfile));
	
	if (empty($mqttcfg)) {
		LOGWARN("Plugin's mqtt config is invalid. Creating a new mqtt config file");
		// Create default mqtt config
		$mqttcfg = new \stdClass();
		$mqttcfg->usemqttgateway = "true";
		$mqttcfg->topic = "awattar";
		$mqttcfg->username = "";
		$mqttcfg->password = "";
		$mqttcfg->server = "";
		$mqttcfg->port = "";
		file_put_contents($mqttconfigfile, json_encode($mqttcfg));
	}
	
	$basetopic = !empty($mqttcfg->topic) ? $mqttcfg->topic : "awattar";
	
	if(is_enabled($mqttcfg->usemqttgateway)) {
		$creds = mqtt_connectiondetails();
	} else {
		$creds['brokerhost'] = $mqttcfg->server;
		$creds['brokerport'] = $mqttcfg->port;
		$creds['brokeruser'] = $mqttcfg->username;
		$creds['brokerpass'] = $mqttcfg->password;
	}
	
	$client_id = uniqid(gethostname()."_client");
	
	$mqtt = new Bluerhinos\phpMQTT($creds['brokerhost'],  $creds['brokerport'], $client_id);

	LOGINF("Connecting to MQTT broker (" . $creds['brokerhost'] . ":". $creds['brokerport'] .", User " . $creds['brokeruser'].")");
	if( $mqtt->connect(true, NULL, $creds['brokeruser'], $creds['brokerpass'] ) ) {
		// Connected
		LOGOK("Connected. Sending values");
		//array_walk($result_dataset, 'mqtt_base_array', $mqtt);
		//array_walk_recursive($result_dataset, 'mqtt_publish_value', $mqtt);
		$flat_array = flatten($result_dataset);
		foreach($flat_array as $topic => $value) {
			$fulltopic = $basetopic . "/" . $topic;
			// LOGDEB("   $fulltopic = $value");
			$mqtt->publish($fulltopic, $value, 0, 1);
		}
		LOGOK("MQTT publishing finished");
		$mqtt->close();
	} else {
		LOGCRIT("Could not connect to MQTT Broker");
	}

}
function flatten($array, $prefix = '') {
    $result = array();
    foreach($array as $key=>$value) {
        if(is_array($value)) {
            $result = $result + flatten($value, $prefix . $key . '/');
        }
        else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}