<?php
if(Settings::pluginGet('oirc_posts')){
	$topicname = '';
	$oirc_postnotification = Settings::pluginGet('oirc_postnotification');
	if(strpos($oirc_postnotification,'{TOPIC}') !== false || strpos($oirc_postnotification,'{SUBJECT}') !== false){
		$topicname = getTopicName($tid);
	}
	sendToOmnomIRC(strtr($oirc_postnotification,array(
		'{COLOR}' => "\x03",
		'{NAME}' => ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']),
		'{TOPIC}' => $topicname,
		'{SUBJECT}' => $topicname,
		'{TOPICID}' => $tid,
		'{POSTID}' => $pid
	)),0);
}
?>