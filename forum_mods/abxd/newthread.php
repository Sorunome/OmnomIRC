<?php
if(Settings::pluginGet('oirc_topics')){
	sendToOmnomIRC(strtr(Settings::pluginGet('oirc_topicnotification'),array(
		'{COLOR}' => "\x03",
		'{NAME}' => ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']),
		'{TOPIC}' => $thread['title'],
		'{SUBJECT}' => $thread['title'],
		'{TOPICID}' => $thread['id'],
		'{POSTID}' => $pid
	)),0);
}
?>