<?php
// Do initial setup stuff here
include('../lib/common.php');

function hook_is_op($id){
	$user = Fetch(Query("select * from {users} where id={0}", $id));
	return $user['powerlevel']>=1;
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	global $loguser;
	
	return $loguser['name']!='' && $loguser['powerlevel']>=0 && !isIPBanned($_SERVER['REMOTE_ADDR']);
}
function hook_get_login(){
	// return based on forum login the nick and the uid, in an array as shown
	global $loguser;
	$nick = ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']);
	$uid = $loguser['id'];
	return array(
		'nick' => $nick,
		'uid' => (int)$uid
	);
}
?>
