<?php
if(!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang,array(
	'ACP_OIRC_TITLE' => 'OmnomIRC Settings',
	'ACP_OIRC_GENERAL' => 'General',
	'ACP_OIRC_NOTIFICATIONS' => 'Notifications',
	'ACP_OIRC_CHECKLOGIN' => 'Check Login',
	'ACP_OIRC_ADMIN' => 'OmnomIRC admin pannel',
	
	'ACL_U_OIRC_VIEW' => 'View OmnomIRC',
	'ACL_M_OIRC_OP' => 'Is OmnomIRC OP',
	
	'UCP_OIRC_SETTINGS' => 'OmnomIRC Settings',
	'UCP_OIRC_SETTING_SAVED' => 'Settings saved!',
));

?>