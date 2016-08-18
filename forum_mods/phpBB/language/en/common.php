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
	
	'ACP_OIRC_NO_NOT' => 'no notifications',
	'ACP_OIRC_OTHER_NOT' => 'other...',
	
	'ACL_U_OIRC_VIEW' => 'View OmnomIRC',
	'ACL_M_OIRC_OP' => 'Is OmnomIRC OP',
	'ACP_OIRC_CHAN' => 'Notification channel:',
	'ACP_OIRC_CHAN_DESC' => 'Notifications of this board will land in the set OmnomIRC channel',
	
	'UCP_OIRC_SETTINGS' => 'OmnomIRC Settings',
	'UCP_OIRC_SETTING_SAVED' => 'Settings saved!',
));
