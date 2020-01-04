<?php

require_once "loxberry_web.php";
require_once LBPBINDIR . "/defines.php";

$navbar[1]['active'] = null;
$navbar[2]['active'] = True;


$L = LBSystem::readlanguage("language.ini");
$template_title = "aWATTar Plugin";
$helplink = "https://www.loxwiki.eu/x/woDPAw";
$helptemplate = "help.html";

LBWeb::lbheader($template_title, $helplink, $helptemplate);

?>

<style>
.mono {
	font-family:monospace;
	font-size:110%;
	font-weight:bold;
	color:green;

}
#overlay 
{
  display: none !important;
}


/* Custom indentations are needed because the length of custom labels differs from
   the length of the standard labels */
.custom-size-flipswitch.ui-flipswitch .ui-btn.ui-flipswitch-on {
	text-indent: -5.9em;
}
.custom-size-flipswitch.ui-flipswitch .ui-flipswitch-off {
	text-indent: 0.5em;
}
/* Custom widths are needed because the length of custom labels differs from
   the length of the standard labels */
.custom-size-flipswitch.ui-flipswitch {
	width: 8.875em;
}
.custom-size-flipswitch.ui-flipswitch.ui-flipswitch-active {
	padding-left: 7em;
	width: 1.875em;
}
@media (min-width: 28em) {
	/*Repeated from rule .ui-flipswitch above*/
	.ui-field-contain > label + .custom-size-flipswitch.ui-flipswitch {
	width: 1.875em;
	}
}

.dayname {
	width:120px;
	text-align:left;
}
.daycheckbox {
	width:28px;
	text-align:right;
}



</style>


<!-- Adviser -->

<div class="wide">Adviser</div>
<p>
	Der Adviser berechnet für dich die günstigste (und teuerste) Startzeit für deine Geräte, die länger als eine Stunde laufen. Gib dem Gerät einen Namen, stelle ein, wieviele Stunden das Gerät läuft, und definiere Zeiten, in denen das Gerät <u>nicht</u> laufen soll.
</p>
<p class="hint">
	<b>Tipp für die Ausnahmen:</b> Mit Tab und Leertaste kannst du schnell viele Häkchen setzen!
</p> 

<!-- Device Section -->

<div id="devices">
</div>

<!-- Device Section End -->

<!-- Page button area -->

<div style="display:flex;align-items:center;justify-content:center;height:16px;min-height:16px">
	<span id="savemessages"></span>
</div>
<div style="display:flex;align-items:center;justify-content:center;">
	<button class="ui-btn ui-shadow ui-icon-plus ui-btn-icon-left" data-icon="plus" id="adddevice" data-inline="true">Neues Gerät hinzufügen</button>
</div>

<!-- Page button area End -->

<!-- We paste the config json into this hidden div, to parse it later by JS -->

<div id="jsonconfig" style="display:none">
<?php
$configjson = file_get_contents($configfile);
if( !empty($configjson) or !empty( json_decode($configjson) ) ) {
	echo $configjson;
} else {
	echo "{}";
}
?>
</div>

<?php
LBWeb::lbfooter();
?>

<!-- Page End -->

<!-- We use templates to generate the UI by JavaScript -->

<template id="deviceTemplate"> 
<!-- Template for a device -->
	<form id="form_#DEVICEUID" onsubmit="return false;">

	<div id="device_#DEVICEUID" class="device" style="border: 1px solid #ccc; border-radius: 16px; border;padding:10px">
		<div style="height:20px;"></div>
		<div class="lb_flex-container">
			<div	class="lb_flex-item-label">
				<label for="CONFIG.adviser.#DEVICEUID.devicename">Gerätename</label>
			</div>
			<div	class="lb_flex-item-spacer"></div>
			<div	class="lb_flex-item">
				<input name="CONFIG.adviser.#DEVICEUID.devicename" id="CONFIG.adviser.#DEVICEUID.devicename">
			</div>
			<div	class="lb_flex-item-spacer"></div>
			<div	class="lb_flex-item-help hint">
				Gib deinem Gerät einen Namen. Dieser wird als Topic bei der MQTT-Übertragung verwendet.
			</div>
			<div	class="lb_flex-item-spacer"></div>
		</div>

		<div class="lb_flex-container">
			<div	class="lb_flex-item-label">
				<label for="CONFIG.adviser.#DEVICEUID.deviceduration">Laufzeit in Stunden</label>
			</div>
			<div	class="lb_flex-item-spacer"></div>
			<div	class="lb_flex-item">
				<input name="CONFIG.adviser.#DEVICEUID.deviceduration" id="CONFIG.adviser.#DEVICEUID.deviceduration"  type="number" data-clear-btn="false">
			</div>
			<div	class="lb_flex-item-spacer"></div>
			<div	class="lb_flex-item-help hint">
				Wieviele Stunden läuft dieses Gerät (z.B. Geschirrspüler 3 Stunden)
			</div>
			<div	class="lb_flex-item-spacer"></div>
		</div>

		<!-- Device Excludes Collapsible and table header -->
		<div data-role="collapsible" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" id="collapsible_#DEVICEUID">
			<h4>Ausnahmen pro Tag</h4>
		
			<table class="daytable">
				<tr>
					<th class="dayname">
						Wochentag
					</th>
					<th class="daycheckbox">00</th>
					<th class="daycheckbox">01</th>
					<th class="daycheckbox">02</th>
					<th class="daycheckbox">03</th>
					<th class="daycheckbox">04</th>
					<th class="daycheckbox">05</th>
					<th class="daycheckbox">06</th>
					<th class="daycheckbox">07</th>
					<th class="daycheckbox">08</th>
					<th class="daycheckbox">09</th>
					<th class="daycheckbox">10</th>
					<th class="daycheckbox">11</th>
					<th class="daycheckbox">12</th>
					<th class="daycheckbox">13</th>
					<th class="daycheckbox">14</th>
					<th class="daycheckbox">15</th>
					<th class="daycheckbox">16</th>
					<th class="daycheckbox">17</th>
					<th class="daycheckbox">18</th>
					<th class="daycheckbox">19</th>
					<th class="daycheckbox">20</th>
					<th class="daycheckbox">21</th>
					<th class="daycheckbox">22</th>
					<th class="daycheckbox">23</th>
				</tr>
				<!--#DAYTEMPLATE-->
			</table>
		</div>
		<div style="display:flex;align-items:center;justify-content:center;">
			<button class="ui-btn ui-btn-icon-left ui-icon-delete" id="delete_#DEVICEUID" data-inline="true">Gerät löschen</button>&nbsp;
			<button class="ui-btn ui-btn-icon-left ui-icon-check" id="save_#DEVICEUID" data-inline="true">Änderungen speichern</button>
		</div>
		<div style="text-align:center;" id="messages_#DEVICEUID"></div>
		
	</div>
	</form>
</template>

<template id="weekdayTemplate">
<!-- Template for a week's day -->
<tr>
	<td class="dayname">#DAYNAME</td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.0" id="#DEVICEUID.excludes.#WEEKDAY.0" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.1" id="#DEVICEUID.excludes.#WEEKDAY.1" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.2" id="#DEVICEUID.excludes.#WEEKDAY.2" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.3" id="#DEVICEUID.excludes.#WEEKDAY.3" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.4" id="#DEVICEUID.excludes.#WEEKDAY.4" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.5" id="#DEVICEUID.excludes.#WEEKDAY.5" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.6" id="#DEVICEUID.excludes.#WEEKDAY.6" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.7" id="#DEVICEUID.excludes.#WEEKDAY.7" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.8" id="#DEVICEUID.excludes.#WEEKDAY.8" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.9" id="#DEVICEUID.excludes.#WEEKDAY.9" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.10" id="#DEVICEUID.excludes.#WEEKDAY.10" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.11" id="#DEVICEUID.excludes.#WEEKDAY.11" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.12" id="#DEVICEUID.excludes.#WEEKDAY.12" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.13" id="#DEVICEUID.excludes.#WEEKDAY.13" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.14" id="#DEVICEUID.excludes.#WEEKDAY.14" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.15" id="#DEVICEUID.excludes.#WEEKDAY.15" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.16" id="#DEVICEUID.excludes.#WEEKDAY.16" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.17" id="#DEVICEUID.excludes.#WEEKDAY.17" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.18" id="#DEVICEUID.excludes.#WEEKDAY.18" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.19" id="#DEVICEUID.excludes.#WEEKDAY.19" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.20" id="#DEVICEUID.excludes.#WEEKDAY.20" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.21" id="#DEVICEUID.excludes.#WEEKDAY.21" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.22" id="#DEVICEUID.excludes.#WEEKDAY.22" value="on"></td>
	<td class="daycheckbox"><input type="checkbox" name="CONFIG.adviser.#DEVICEUID.excludes.#WEEKDAY.23" id="#DEVICEUID.excludes.#WEEKDAY.23" value="on"></td>
</tr>
</template>

<!-- JAVASCRIPT -->

<script>

var config;

$( document ).ready(function() {

	// We parse the json that was written to a hidden div
	config = JSON.parse( $("#jsonconfig").text() );
	
	// First we create the device forms for each device from the config
	createDeviceBlocksFromConfig();
	// Then, we fill up all the values in the form
	formFill();
	// If we have dependencies to view or hide fields, do this in viewhide()
	viewhide();
	
	// Click handler for Add New Device button
	$('#adddevice').click(function() { addDevice(); });

});

function addDevice()
{
	// Create a unique device id
	var newDevId = uuidv4();
	// Create a device block with the new deviceUid
	createDeviceBlock(newDevId);
	// Save message and handler for changes od the device
	$('#messages_'+newDevId).html('<span style="color:red">Unsaved</span>');
	$('#device_'+newDevId+' input').change(function() { 
        $('#messages_'+newDevId).html('<span style="color:red">Unsaved changes</span>');
	}); 
	
}

/* Function to create a UID */
function uuidv4() {
  return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
    (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
  );
}

function viewhide()
{

	// In Adviser, we have nothing to hide

/*
	if( $("#MQTTUseMQTTGateway").is(":checked") ) {
		$(".ownbroker").fadeOut();
	} else {
		$(".ownbroker").fadeIn();
	}
	
	if( $("#country").val() == "DE" ) {
		$(".country").fadeOut();
	} else {
		$(".country").fadeIn();
	}
*/	
	
}

/* Read the config and fill in all the form fields */
function formFill()
{
	console.log("formFill");
	
	// Country selection
	if (typeof config.general.country !== 'undefined') $("#country").val( config.general.country ).attr('selected', true).siblings('option').removeAttr('selected');
	$("#country").selectmenu("refresh", true);
	
	// Token
	if (typeof config.general.token !== 'undefined') $("#token").val( config.general.token );
	
	// Price modifier
	if (typeof config.general.pricemodifier !== 'undefined') $("#pricemodifier").val( config.general.pricemodifier );
	
	if( typeof mqttconfig !== 'undefined') {
		if (typeof mqttconfig.usemqttgateway !== 'undefined') {
			if( is_enabled(mqttconfig.usemqttgateway) ) 
				$("#MQTTUseMQTTGateway").prop('checked', mqttconfig.usemqttgateway).checkboxradio('refresh');
		}
		if (typeof mqttconfig.topic !== 'undefined') $("#MQTTTopic").val( mqttconfig.topic );
		if (typeof mqttconfig.server !== 'undefined') $("#BrokerServer").val( mqttconfig.server );
		if (typeof mqttconfig.port !== 'undefined') $("#BrokerPort").val( mqttconfig.port );
		if (typeof mqttconfig.username !== 'undefined') $("#BrokerUsername").val( mqttconfig.username );
		if (typeof mqttconfig.password !== 'undefined') $("#BrokerPassword").val( mqttconfig.password );
	}
	
	// Adviser settings
	$.each( config.adviser, function(deviceUid, deviceObj){
		// console.log(deviceUid, deviceObj);
		// Normal fields
		if(typeof deviceObj.devicename !== 'undefined') $('#CONFIG\\.adviser\\.'+deviceUid+'\\.devicename').val( deviceObj.devicename );
		if(typeof deviceObj.deviceduration !== 'undefined') $('#CONFIG\\.adviser\\.'+deviceUid+'\\.deviceduration').val( deviceObj.deviceduration );
	
		// Loop the days
		$.each( deviceObj.excludes, function(excludeDay, excludeDayObj){
			//console.log(excludeDay, excludeDayObj);
			
			// Loop the hours
			$.each(excludeDayObj, function(excludeHour, excludeHourVal){ 
				// Check the checkboxes
				//console.log("excludeHour", excludeHour, excludeHourVal);
				if(is_enabled(excludeHourVal)) $('#'+deviceUid+'\\.excludes\\.'+excludeDay+'\\.'+excludeHour).prop( 'checked', true );
			});	
		});
	});
}

/* For every device in the config, create a html block */
function createDeviceBlocksFromConfig()
{
	$.each( config.adviser, function(deviceUid, deviceObj){	
		createDeviceBlock(deviceUid);
		$('#messages_'+deviceUid).html('');
	$('#device_'+deviceUid+' input').change(function() { 
        $('#messages_'+deviceUid).html('<span style="color:red">Unsaved changes</span>');
	}); 
	});
}

/* Use the templates to generate html with unique id's with the deviceUid */
function createDeviceBlock(deviceUid)
{
	console.log("createDeviceBlock", deviceUid);
	
	// Copy device template to a new element
	let templateBlock = $('#deviceTemplate');
	let deviceBlock = templateBlock.html();

	// Add weekday template to new element
	
	deviceBlock = deviceBlock.replace(/<!--#DAYTEMPLATE-->/g, 
		createWeekdayBlock("Montag", 1) +
		createWeekdayBlock("Dienstag", 2) +
		createWeekdayBlock("Mittwoch", 3) +
		createWeekdayBlock("Donnerstag", 4) +
		createWeekdayBlock("Freitag", 5) +
		createWeekdayBlock("Samstag", 6) +
		createWeekdayBlock("Sonntag", 0)
	);
	//console.log("tableEnd after", tableEnd);
	
	deviceBlock = deviceBlock.replace(/#DEVICEUID/g, deviceUid);
	
	// console.log("tableEnd after", tableEnd);
	// console.log("deviceBlock after", deviceBlock);
	
	$(deviceBlock).appendTo("#devices");
	$("#collapsible_"+deviceUid).collapsible().trigger("create");
	
	$("#save_"+deviceUid).click(function(){ saveapply(deviceUid); });
	$("#delete_"+deviceUid).click(function(){ saveapply(deviceUid, "delete"); });
	
}

/* Create a day line (0-23) from template */
function createWeekdayBlock(dayName, dayId)
{
		// console.log("createWeekdayBlock", dayName, dayId);
		let weekdayTemplate = $('#weekdayTemplate');
		
		weekdayBlock = weekdayTemplate.html();
		weekdayBlock = weekdayBlock.replace(/#DAYNAME/g, dayName);
		weekdayBlock = weekdayBlock.replace(/#WEEKDAY/g, dayId);
		
		// var weekdayBlock = weekdayTemplate.html().replace(/#DAYNAME/, dayName).replace(/#WEEKDAY/, dayId);
		
		// console.log("weekdayBlock", weekdayBlock);
		return weekdayBlock;
}

/* Ajax call to save or delete entries in the config */
function saveapply(deviceUid, action="save") 
{
	console.log("saveapply called with deviceUid", deviceUid, "and action", action);
	$("#messages_"+deviceUid).html("Submitting...");
	$("#messages_"+deviceUid).css("color", "grey");
	
	var values;
	
	// Depending on the action, send different data to the server
	switch (action) {
		// Save all data of deviceUid
		case "save":
			var posturl = "ajax-handler.php?action=saveconfig";
			
			// Handle checkboxes: If checkbox is disabled, 
			/* Get input values from form */
			values = $("#form_"+deviceUid).serializeArray();
			console.log("Values", values);

			/* Because serializeArray() ignores unset checkboxes and radio buttons: */
			values = values.concat(
				$('#device_'+deviceUid+' input[type=checkbox]:not(:checked)').map(
						function() {
							// console.log("Checkbox", this.name, "Value", this.value);
				return {"name": this.name, "value": "off"}
				}).get()
			);	
			break;
		
		// Delete the deviceUid tree
		case "delete":
			var posturl = "ajax-handler.php?action=deletetree";
			values = { deletetree: 'CONFIG.adviser.'+deviceUid };
			break;
	}	
	
	$.post( posturl, values )
	.done(function( data ) {
		console.log("Done:", data);
		$("#messages_"+deviceUid).html("Erfolgreich gespeichert");
		$("#messages_"+deviceUid).css("color", "green");
		
		if(action == "delete") {
			$('#device_'+deviceUid).fadeOut(500, function(){$('#device_'+deviceUid).remove()});	
		}
		
		console.log("Response data", data, "Action", action);
		config = data.CONFIG;
		mqttconfig = data.MQTT;
		
		formFill();
	})
	.fail(function( error, textStatus, errorThrown ) {
		console.log("Fail:", error, textStatus, errorThrown);
		$("#savemessages").html("Error "+error.status+": "+error.responseJSON.error);
		$("#savemessages").css("color", "red");
		
	});
}

/* A simple is_enabled function as known from LoxBerry's Perl and PHP SDK */
function is_enabled( checkVal ) 
{
	// console.log("is_enabled", checkVal);
	if( typeof checkVal === 'undefined' ) return false;
	
	enabled = [ true, "true", 1, "1", "on", "yes", "enable", "enabled", "select", "selected", "checked" ];
	checkVal = checkVal.trim().toLowerCase();
	return enabled.includes(checkVal);
}

</script>





