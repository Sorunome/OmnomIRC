<?php
function oirc_disppages_user()
{
	global $pluginSettings;
	if(!empty($_POST['oirc_options_changetags'])){
		unset($_POST['oirc_options_changetags']);
		$pages = array();
		foreach(getOircPages() as $p){
			$pages[$p] = !empty($_POST['oirc_options_enabledTags'][$p]);
		}
		unset($_POST['oirc_options_enabledTags']);
		//$_POST['oirc_disppages_user'] = serialize($pages);
		
		$pluginSettings['oirc_disppages_user'] = serialize($pages);
		return true;
	}
	return false;
}

if(!($s = unserialize(getSetting('oirc_disppages_user',false)))){
	if(!($s = unserialize(Settings::pluginGet('oirc_disppages')))){
		$s = array();
	}
}
print '
<style type="text/css">
#oirc_disppages_user,label[for="oirc_disppages_user"]{
	visibility:hidden;
}
</style>';
getOircPagePicker('oirc_disppages_user',$s);
$general['options']['items']['oirc_disppages_user'] = array(
	'caption' => 'DO NOT EDIT THIS!!!!',
	'type' => 'text',
	'value' => $s,
	'callback' => 'oirc_disppages_user',
);
?>
