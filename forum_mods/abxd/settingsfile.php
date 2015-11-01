<?php
// this file will be called always.
if($title != __("Edit settings")){
	// we only want to run this block once
	function generateOircSigURL(){
		global $config,$only_include_oirc;
		$only_include_oirc = true;
		include_once('plugins/OmnomIRC/checkLogin/index.php');
		$nick = '*';
		$time = (string)(time() - 60*60*24 + 60); // the sig key is only valid for one min!
		$uid = rand();
		$network = 0;
		$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$network.$config['sigKey'].$time);
		return 'nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&time='.$time.'&network='.$network.'&id='.$uid.'&noLoginErrors&serverident';
	}

	function sendToOmnomIRC($message,$channel){
		global $config;
		$sigurl = generateOircSigURL();
		file_get_contents($config['oircUrl'].'/message.php?message='.base64_url_encode($message).'&channel='.$channel.'&serverident&'.$sigurl);
	}
	
	function getTopicName($tid){
		$t = Fetch(Query("select title from {threads} where id={0}",$tid));
		return $t['title'];
	}
}

global $title;
$boardurl = 'http'.($_SERVER['SERVER_PORT'] == 443?'s':'').'://'.(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']).$_SERVER['SCRIPT_NAME'];
$settings = array(
	'oirc_title' => array(
		'type' => 'text',
		'name' => 'OmnomIRC title',
		'default' => 'OmnomIRC chat'
	),
	'oirc_height' => array(
		'type' => 'integer',
		'name' => 'OmnomIRC frame height',
		'default' => '280'
	),
	'oirc_topics' => array(
		'type' => 'boolean',
		'name' => 'Notify of new threads',
		'default' => true
	),
	'oirc_posts' => array(
		'type' => 'boolean',
		'name' => 'Notify of new replies',
		'default' => true
	),
	'oirc_edits' => array(
		'type' => 'boolean',
		'name' => 'Notify of edits',
		'default' => true
	),
	'oirc_topicnotification' => array(
		'type' => 'text',
		'name' => 'Notification message for new threads',
		'default' => '{COLOR}10New thread by {COLOR}03{NAME} {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'?page=thread&id={TOPICID}'
	),
	'oirc_postnotification' => array(
		'type' => 'text',
		'name' => 'Notification message for new replies',
		'default' => '{COLOR}10New reply by {COLOR}03{NAME} {COLOR}10in {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'?page=thread&id={TOPICID}#{POSTID}'
	),
	'oirc_editnotification' => array(
		'type' => 'text',
		'name' => 'Notification message for post edits',
		'default' => '{COLOR}10Edit by {COLOR}03{NAME} {COLOR}10on {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'?page=thread&id={TOPICID}#{POSTID}'
	),
	'oirc_frameurl' => array(
		'type' => 'text',
		'name' => 'DO NOT EDIT THIS!!!!',
		'default' => $config['oircUrl'].'/index.php?network='.$config['network']
	),
);
if($title != __("Edit settings")){
	return;
}

// starting here are the settings-specific things. It wouldn't hurt to run that always but in theory it's a performance decrease, so we don't.

global $config,$only_include_oirc;
$only_include_oirc = true;
include_once('plugins/OmnomIRC/checkLogin/index.php');

$settings = array_merge($settings,array(
	'oirc_config_installed' => array(
		'type' => 'boolean',
		'name' => 'Installed',
		'default' => $config['installed']
	),
	'oirc_config_sigKey' => array(
		'type' => 'text',
		'name' => 'Signature key',
		'default' => $config['sigKey']
	),
	'oirc_config_network' => array(
		'type' => 'integer',
		'name' => 'Network ID',
		'default' => $config['network']
	),
	'oirc_config_oircUrl' => array(
		'type' => 'text',
		'name' => 'OmnomIRC url',
		'default' => $config['oircUrl']
	)
));

print '
<style type="text/css">
#oirc_frameurl,label[for="oirc_frameurl"]{
	visibility:hidden;
}
</style>
';
if(isset($_POST['_plugin'])){
	// we are saving!
	if(isset($_POST['oirc_config_installed'])){
		$config['installed'] = $_POST['oirc_config_installed'] == 1;
	}
	if(isset($_POST['oirc_config_sigKey'])){
		$config['sigKey'] = $_POST['oirc_config_sigKey'];
	}
	if(isset($_POST['oirc_config_network'])){
		$config['network'] = (int)$_POST['oirc_config_network'];
	}
	if(isset($_POST['oirc_config_oircUrl'])){
		$config['oircUrl'] = $_POST['oirc_config_oircUrl'];
	}
	$_POST['oirc_frameurl'] = $config['oircUrl'].'/index.php?network='.$config['network'];
	if(!writeConfig()){
		Alert(__("Please make sure that the OmnomIRC config file is writable!"));
	};
}
?>