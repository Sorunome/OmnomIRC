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
	'OIRC_PAGES' => 'Pages on which to show OmnomIRC',
	'OIRC_PAGES_INDEX' => 'Index',
	'OIRC_PAGES_BOARDS' => 'Boards',
	'OIRC_PAGES_THREADS' => 'Threads',
	'OIRC_PAGES_PROFILES' => 'Profiles',
	'OIRC_PAGES_MODERATION' => 'Moderation',
	'OIRC_PAGES_MISC' => 'Misc',
	'OIRC_PAGES_SWITCH_ADVANCED' => 'Switch to advanced settings',
	'OIRC_PAGES_SWITCH_SIMPLE' => 'Switch to simple settings',
	'OIRC_PAGES_CHECK_ALL' => 'Check all:',
));
