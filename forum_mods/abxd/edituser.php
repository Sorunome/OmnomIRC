<?php
function disableomnom()
{
	global $pluginSettings;
	$pluginSettings['disableomnom'] = urlencode($_POST['disableomnom']);
	return true;
}

$general['options']['items']['disableomnom'] = array(
	"caption" => "Disable OmnomIRC",
	"type" => "checkbox",
	"value" => getSetting("disableomnom", true),
	"callback" => "disableomnom",
);
?>
