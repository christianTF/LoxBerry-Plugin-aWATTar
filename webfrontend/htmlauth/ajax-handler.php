<?php

require_once "loxberry_system.php";
require_once "defines.php";

if( $_GET["action"] == "saveconfig" ) {
	$data = array();
	foreach( $_POST as $key => $value ) {
		// PHP's $_POST converts dots of post variables to underscores
		$data = generateNew($data, explode("_", $key), 0, $value);
	}
	$jsonstr = json_encode($data, JSON_PRETTY_PRINT);
	if($jsonstr) {
		if ( file_put_contents( CONFIGFILE, $jsonstr ) == false ) {
			sendresponse( 500, "application/json", '{ "error" : "Could not write config file" }' );
		} else {
			sendresponse ( 200, "application/json", file_get_contents(CONFIGFILE) );
		}
	} else {
		sendresponse( 500, "application/json", '{ "error" : "Submitted data are not valid json" }' );
	}
	exit(1);
}

if ( $_GET["action"] == "getsummary" ) {
	shell_exec("php $lbphtmlauthdir/ze.php action=summary");
	if ( ! file_exists(LOGINFILE) ) {
		sendresponse ( 500, "application/json", '{ "error" : "Could not query summary" }' );
	}
	sendresponse ( 200, "application/json", file_get_contents( LOGINFILE ) );
}

if ( $_GET["action"] == "getbattery" ) {
	$battfilename = TMPPREFIX . "batt_" . $_GET["vin"];
	if (empty($_GET["vin"]) || !file_exists( $battfilename ) ) {
		sendresponse ( 500, "application/json", '{ "error" : "Could not query battery data" }' );
	}
	sendresponse ( 200, "application/json", file_get_contents( $battfilename ) );
}

if ( $_GET["action"] == "getconditionlast" ) {
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
	
	header($_SERVER["SERVER_PROTOCOL"]." $httpstatus ". $codes[$httpstatus]); 
	header("Content-Type: $contenttype");
	
	if($response) {
		echo $response;
	}
	exit(0);
}


?>
