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
));

?>