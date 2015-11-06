<?php
if (!defined('SMF'))
	require_once('SSI.php');
global $smcFunc, $modSettings, $boardurl, $sourcedir, $boarddir, $oirc_config;

remove_integration_function('integrate_pre_include','$sourcedir/OmnomIRC.php');
remove_integration_function('integrate_menu_buttons','loadOircActions');
remove_integration_function('integrate_load_permissions','loadOircPermissions');

// save the config as we may be just updating the package
$only_include_oirc = true;
include_once($boarddir.'/checkLogin/index.php');

updateSettings(array(
	'oirc_backup_config' => serialize($oirc_config)
));

deltree($boarddir.'/checkLogin');

?>