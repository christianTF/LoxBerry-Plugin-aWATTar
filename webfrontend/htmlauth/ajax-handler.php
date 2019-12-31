<?php

require_once "loxberry_system.php";
require_once LBPBINDIR . "/defines.php";

if( isset($_GET["action"]) && $_GET["action"] == "saveconfig" ) {
	$data = array();
	foreach( $_POST as $key => $value ) {
		// PHP's $_POST converts dots of post variables to underscores
		$data = generateNew($data, explode("_", $key), 0, $value);
	}
	
	error_log("POST data:\n" . var_export($data, true));
	
	$cfg = json_decode( file_get_contents($configfile), true );
	
	if( isset($data['CONFIG']['general']) ) {
		// If general data are submitted by POST, delete 'general' tree in current config
		unset($cfg['general']);
	}
		
	$cfg = array_merge($cfg, $data['CONFIG']);
	
	// error_log("MERGED data:\n" . var_export($cfg, true));
	
	file_put_contents( $configfile, json_encode($cfg, JSON_PRETTY_PRINT) );
	file_put_contents( $mqttconfigfile, json_encode($data['MQTT'], JSON_PRETTY_PRINT) );
	
	$jsonstr = json_encode( array( 'CONFIG' => $cfg, 'MQTT' => $data['MQTT']), JSON_PRETTY_PRINT );
	// $jsonstr = json_encode($data, JSON_PRETTY_PRINT);
	// DEBUG
	sendresponse( 200, "application/json", $jsonstr );
	
	// if($jsonstr) {
		// if ( file_put_contents( $configfile , $jsonstr ) == false ) {
			// sendresponse( 500, "application/json", '{ "error" : "Could not write config file" }' );
		// } else {
			// sendresponse ( 200, "application/json", file_get_contents(CONFIGFILE) );
		// }
	// } else {
		// sendresponse( 500, "application/json", '{ "error" : "Submitted data are not valid json" }' );
	// }
	exit(1);
}

if ( isset($_GET["action"]) && $_GET["action"] == "getsummary" ) {
	shell_exec("php $lbphtmlauthdir/ze.php action=summary");
	if ( ! file_exists(LOGINFILE) ) {
		sendresponse ( 500, "application/json", '{ "error" : "Could not query summary" }' );
	}
	sendresponse ( 200, "application/json", file_get_contents( LOGINFILE ) );
}

if ( isset($_GET["action"]) && $_GET["action"] == "getbattery" ) {
	$battfilename = TMPPREFIX . "batt_" . $_GET["vin"];
	if (empty($_GET["vin"]) || !file_exists( $battfilename ) ) {
		sendresponse ( 500, "application/json", '{ "error" : "Could not query battery data" }' );
	}
	sendresponse ( 200, "application/json", file_get_contents( $battfilename ) );
}

if ( isset($_GET["action"]) && $_GET["action"] == "getconditionlast" ) {
	$condfilename = TMPPREFIX . "cond_" . $_GET["vin"];
	shell_exec("php $lbphtmlauthdir/ze.php action=conditionlast vin=" . $_GET["vin"]);
	if ( ! file_exists($condfilename) ) {
		sendresponse ( 500, "application/json", '{ "error" : "Could not query air-condition data" }' );
	}
	sendresponse ( 200, "application/json", file_get_contents( $condfilename ) );
}



sendresponse ( 501, "application/json",  '{ "error" : "No supported action given." }' );
exit(1);

// $configjson = file_get_contents(CONFIGFILE);
// if( !empty($configjson) or !empty( json_decode($configjson) ) ) {
	// echo $configjson;
// } else {
	// echo "{}";
// }


function generateNew($array, $keys, $currentIndex, $value)
    {
        if ($currentIndex == count($keys) - 1)
        {
            $array[$keys[$currentIndex]] = $value;
        }
        else
        {
            if (!isset($array[$keys[$currentIndex]]))
            {
                $array[$keys[$currentIndex]] = array();
            }

            $array[$keys[$currentIndex]] = generateNew($array[$keys[$currentIndex]], $keys, $currentIndex + 1, $value);
        }

        return $array;
    }








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
