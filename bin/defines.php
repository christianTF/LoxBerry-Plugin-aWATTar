<?php

$configfile = LBPCONFIGDIR . "/awattar.json";
$pricefile = "/tmp/awattar_pricedata.json";
$resultfile = "/tmp/awattar_resultdata.json";
$mqttconfigfile = LBPCONFIGDIR . "/mqtt.json";

// Constants
// const WEEKDAY = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
const WEEKDAY = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

// The Navigation Bar
$navbar[1]['Name'] = "Einstellungen";
$navbar[1]['URL'] = 'index.php';
 
$navbar[2]['Name'] = "Adviser";
$navbar[2]['URL'] = 'adviser.php';
 
$navbar[99]['Name'] = "Logfiles";
$navbar[99]['URL'] = '/admin/system/logmanager.cgi?package='.LBPPLUGINDIR;
$navbar[99]['target'] = '_blank';
 
 
