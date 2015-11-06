<?php
if(Settings::pluginGet('oirc_topics')){
	$chan = getOircSendToChan($thread['id']);
	if($chan != -1){
		sendToOmnomIRC(strtr(Settings::pluginGet('oirc_topicnotification'),array(
			'{COLOR}' => "\x03",
			'{NAME}' => ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']),
			'{TOPIC}' => $thread['title'],
			'{SUBJECT}' => $thread['title'],
			'{TOPICID}' => $thread['id'],
			'{POSTID}' => $pid
		)),$chan);
	}
?>