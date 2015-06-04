<?php
// Do initial setup stuff here
define('IN_MYBB',1);
define('NO_ONLINE',1);
require_once "../global.php";

function hook_get_group($id){
	// $id is the int of the user, should return a string to identify the group
	$user = get_user((int) $id);
	return $user['usergroup'] or '';
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	global $mybb;
	return $mybb->user['username']!='' && !$mybb->user['isbannedgroup'];
}
function hook_get_login(){
	// return based on forum login the nick and the uid, in an array as shown
	global $mybb;
	$nick = $mybb->user['username'];
	$uid = $mybb->user['uid'];
	return array(
		'nick' => $nick,
		'uid' => (int)$uid
	);
}
?>
