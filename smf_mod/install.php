<?php
if (!defined('SMF'))
	require_once('SSI.php');
global $smcFunc, $modSettings, $boardurl, $sourcedir, $boarddir, $config;

$smcFunc['db_create_table']('{db_prefix}oirc_postchans', array(
	array('name' => 'id_profile', 'type' => 'int', 'null' => false),
	array('name' => 'channel', 'type' => 'text'),
), array(
	array('type' => 'primary', 'columns' => array('id_profile')),
));



add_integration_function('integrate_pre_include','$sourcedir/OmnomIRC.php',true);
add_integration_function('integrate_load_theme','loadOircActions',true);
add_integration_function('integrate_load_permissions','loadOircPermissions',true);

include_once($sourcedir.'/OmnomIRC.php');
OircMaintenance(); // populates the action array

// keep in mind, we need to "hack" config.json.php file in else it will get over-written again!
file_put_contents($boarddir.'/checkLogin/config.json.php',file_get_contents(realpath(dirname(__FILE__)).'/checkLogin/config.json.php'));
if(!empty($modSettings['oirc_backup_config'])){ // we have an old config!
	$only_include_oirc = true;
	include_once($boarddir.'/checkLogin/index.php');
	$config = unserialize($modSettings['oirc_backup_config']);
	writeConfig();
}

if(empty($modSettings['oirc_height'])){
	updateSettings(array(
		'oirc_height' => 280,
		'oirc_title' => 'OmnomIRC Chat',
		'oirc_topics' => 1,
		'oirc_posts' => 1,
		'oirc_edits' => 1,
		'oirc_topicnotification' => '{COLOR}06New {COLOR}10topic by {COLOR}03{NAME} {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'/index.php?topic={TOPICID}',
		'oirc_postnotification' => '{COLOR}06New {COLOR}10post by {COLOR}03{NAME} {COLOR}10in {COLOR}04{TOPIC} {COLOR}'.$boardurl.'/index.php?topic={TOPICID}.msg{POSTID}#msg{POSTID}',
		'oirc_editnotification' => '{COLOR}06New {COLOR}10edit by {COLOR}03{NAME} {COLOR}10on {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'/index.php?topic={TOPICID}.msg{POSTID}#msg{POSTID}',
	));
}

?>