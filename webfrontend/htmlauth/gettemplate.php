<?php

require_once "loxberry_system.php";
require_once "loxberry_log.php";
require_once LBPBINDIR . "/defines.php";
require_once "loxberry_loxonetemplatebuilder.php";

$log = LBLog::newLog( [ "name" => "Template", "nofile" => 1, "stderr" => 1, "loglevel" => 7 ] );



// Convert commandline parameters to $_GET
foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else   
        $_GET[$e[0]]=0;
}

// Destination template
$desttemplate = $_GET["template"];

// Generate human-readable name from $desttemplate
$templatename = rtrim($desttemplate, '/');
$slashpos = strrpos($templatename, "/");
if ($slashpos != false) { 
	$templatename = substr( $templatename, $slashpos+1 );
}
$templatename = ucfirst( $templatename );
LOGINF("Templatename: $templatename");

// Get topic
$mqttcfg = @json_decode(file_get_contents($mqttconfigfile));
$topic = $mqttcfg->topic;

// Read result file
$resultcfg = @json_decode(file_get_contents($resultfile), true );

// Create a flat array from nested array 
$flatresult = flatten( $resultcfg );

// Create the VI
$VIhttp = new VirtualInHttp( [
    "Title" => "$templatename (aWATTar)",
    "Comment" => "created by LoxBerry aWATTar Plugin",
	"Address" => "",
	"PollingTime" => "3600"
] );
LOGINF("Destination Template: $desttemplate");
foreach($flatresult as $key=>$value) {
	if( strncmp( $key, $desttemplate, strlen($desttemplate)) != 0) {
		continue;
	}
	
	$fulltopic = str_replace( '/', '_', $topic.'_'.$key);
	$comment = substr( strrchr( $key, '/' ), 1 );
	LOGDEB("Key $key Comment '$comment' (Topic $fulltopic)");
	// Create VI
	$VIhttp->VirtualInHttpCmd ( [
		"Title" => "$fulltopic",
		"Comment" => $comment,
	] );
}
	
$xml = $VIhttp->output();
// Add BOM
$xml = chr(239) . chr(187) . chr(191) . $xml;

$xmlfilename = "VI_".$templatename." (aWATTar).xml";

// Send download response
header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
header("Cache-Control: public"); // needed for internet explorer
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding: Binary");
header("Content-Length: ".strlen($xml));
header("Content-Disposition: attachment; filename=$xmlfilename");
echo $xml;













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
