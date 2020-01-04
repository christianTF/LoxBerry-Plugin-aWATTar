<?php

require_once "loxberry_system.php";
require_once LBPBINDIR . "/defines.php";

$cfg = json_decode( file_get_contents($configfile) );
$mqttcfg = json_decode( file_get_contents($mqttconfigfile) );

if( isset($_GET["action"]) && $_GET["action"] == "saveconfig" ) {
	
	foreach( $_POST as $key => $value ) {
		// PHP's $_POST converts dots of post variables to underscores
		// $data = generateNew($data, explode("_", $key), 0, $value);
		
		$tree = explode("_", $key);
		if( $tree[0] == 'CONFIG' ) {
			// Changing base config
			$forkobj = $cfg;
		} elseif ( $tree[0] == 'MQTT' ) {
			// Changing mqtt config
			$forkobj = $mqttcfg;
		}
		
		// Only set values if $forkobj really exists
		if( is_object($forkobj) ) {		
			for ( $fork = 1; $fork < count($tree)-1; $fork++ ) {
				error_log("fork $fork is " . $tree[$fork]);
				if( !is_object($forkobj->{$tree[$fork]}) ) {
					// Tree element does not exist
					error_log("Initializing class for " . $tree[$fork]);
					$forkobj->{$tree[$fork]} = new stdClass;
				}
				$forkobj = $forkobj->{$tree[$fork]};
			}
			error_log("Writing to " . $tree[count($tree)-1] . " value " . $value);
			$forkobj->{$tree[count($tree)-1]} = $value;
			unset($forkobj);
		}	
	}
	
	// error_log("cfg data now:\n" . var_export($cfg, true));
	// error_log("mqtt data now:\n" . var_export($mqttcfg, true));
	
	file_put_contents( $configfile, json_encode($cfg, JSON_PRETTY_PRINT) );
	file_put_contents( $mqttconfigfile, json_encode($mqttcfg, JSON_PRETTY_PRINT) );
	
	// Run aWATTar script
	shell_exec("php " . LBPBINDIR."/awattar.php");
	
	$jsonstr = json_encode( array( 'CONFIG' => $cfg, 'MQTT' => $mqttcfg));
	sendresponse( 200, "application/json", $jsonstr );
	
	exit(1);
}

if( isset($_GET["action"]) && $_GET["action"] == "deletetree" && isset($_POST["deletetree"]) ) {
	
		error_log("Action deletetree");
		$tree = explode(".", $_POST["deletetree"]);
		if( $tree[0] == 'CONFIG' ) {
			// Changing base config
			$forkobj = $cfg;
		} elseif ( $tree[0] == 'MQTT' ) {
			// Changing mqtt config
			$forkobj = $mqttcfg;
		} else {
			error_log("Neither CONFIG nor MQTT was sent");
		}
		
		// Only set values if $forkobj really exists
		if( is_object($forkobj) ) {		
			for ( $fork = 1; $fork < count($tree)-1; $fork++ ) {
				error_log("fork $fork is " . $tree[$fork]);
				if( !is_object($forkobj->{$tree[$fork]}) ) {
					// Tree element does not exist
					error_log("Tree element " . $tree[$fork] . " does not exist. Stopping");
					unset($forkobj);
					break;
				}
				$forkobj = $forkobj->{$tree[$fork]};
			}
			if (is_object($forkobj)) {
				error_log("Deleting tree element " . $tree[count($tree)-1]);
				unset ($forkobj->{$tree[count($tree)-1]});
			} else {
				error_log("Nothing deleted.");
			}
		}	
	
	// error_log("cfg data now:\n" . var_export($cfg, true));
	// error_log("mqtt data now:\n" . var_export($mqttcfg, true));
	
	file_put_contents( $configfile, json_encode($cfg, JSON_PRETTY_PRINT) );
	file_put_contents( $mqttconfigfile, json_encode($mqttcfg, JSON_PRETTY_PRINT) );
	
	// Run aWATTar script
	shell_exec("php " . LBPBINDIR."/awattar.php");
	
	$jsonstr = json_encode( array( 'CONFIG' => $cfg, 'MQTT' => $mqttcfg));
	sendresponse( 200, "application/json", $jsonstr );
	
	exit(1);
}


sendresponse ( 501, "application/json",  '{ "error" : "No supported action given." }' );

exit(1);


// function generateNew($array, $keys, $currentIndex, $value)
    // {
        // if ($currentIndex == count($keys) - 1)
        // {
            // $array[$keys[$currentIndex]] = $value;
        // }
        // else
        // {
            // if (!isset($array[$keys[$currentIndex]]))
            // {
                // $array[$keys[$currentIndex]] = array();
            // }

            // $array[$keys[$currentIndex]] = generateNew($array[$keys[$currentIndex]], $keys, $currentIndex + 1, $value);
        // }

        // return $array;
    // }


function sendresponse( $httpstatus, $contenttype, $response = null )
{

$codes = array ( 
	200 => "OK",
	204 => "NO CONTENT",
	304 => "NOT MODIFIED",
	400 => "BAD REQUEST",
	404 => "NOT FOUND",
	405 => "METHOD NOT ALLOWED",
	500 => "INTERNAL SERVER ERROR",
	501 => "NOT IMPLEMENTED"
);
	if(isset($_SERVER["SERVER_PROTOCOL"])) {
		header($_SERVER["SERVER_PROTOCOL"]." $httpstatus ". $codes[$httpstatus]); 
		header("Content-Type: $contenttype");
	} 
	
	if($response) {
		echo $response . "\n";
	}
	exit(0);
}


?>
