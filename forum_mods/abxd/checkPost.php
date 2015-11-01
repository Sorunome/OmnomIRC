<?php
if(CURRENT_PAGE == 'editpost'){ // we only want to do the edit notifications here
	if(Settings::pluginGet('oirc_edits')){
		$topicname = '';
		$oirc_editnotification = Settings::pluginGet('oirc_editnotification');
		if(strpos($oirc_editnotification,'{TOPIC}') !== false || strpos($oirc_editnotification,'{SUBJECT}') !== false){
			$topicname = getTopicName($tid);
		}
		sendToOmnomIRC(strtr($oirc_editnotification,array(
			'{COLOR}' => "\x03",
			'{NAME}' => ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']),
			'{TOPIC}' => $topicname,
			'{SUBJECT}' => $topicname,
			'{TOPICID}' => $tid,
			'{POSTID}' => $pid
		)),0);
	}
}
?>