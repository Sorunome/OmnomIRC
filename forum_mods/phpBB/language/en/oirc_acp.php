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
	'ACP_OIRC_SETTING_SAVED' => 'OmnomIRC Settings Saved!',
	'ACP_OIRC_TOPICS' => 'Enable new topic notifications',
	'ACP_OIRC_POSTS' => 'Enable new post notifications',
	'ACP_OIRC_EDITS' => 'Enable post edit notifications',
	'ACP_OIRC_TOPICNOTIFICATION' => 'Topic notification text',
	'ACP_OIRC_POSTNOTIFICATION' => 'Post notification text',
	'ACP_OIRC_EDITNOTIFICATION' => 'Edit notification text',
	'ACP_OIRC_NOTIFICATION_NOTICE' => 'The notification texts can use the following magic variables:<br><ul>
			<li>{COLOR} - IRC color code</li>
			<li>{NAME} - poster name</li>
			<li>{TOPIC} - posting topic</li>
			<li>{SUBJECT} - posting subject</li>
			<li>{TOPICID} - topic id</li>
			<li>{POSTID} - post id (not for new topic)</li>
		</ul>',
	'ACP_OIRC_TITLE' => 'OmnomIRC frame title',
	'ACP_OIRC_HEIGHT' => 'Height of OmnomIRC frame',
	'ACP_OIRC_CONFIG_INSTALLED' => 'Installed',
	'ACP_OIRC_CONFIG_SIGKEY' => 'Signature key',
	'ACP_OIRC_CONFIG_NETWORK' => 'Network',
	'ACP_OIRC_CONFIG_OIRCURL' => 'OmnomIRC URL',
));

?>