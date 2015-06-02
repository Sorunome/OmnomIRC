<?php
// Do initial setup stuff here
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
if(isset($request)){
	$request->enable_super_globals();
}
function hook_get_group($id){
	// $id is the int of the user, should return a string to identify the group
	global $db;
	$sql = 'SELECT group_id FROM '.USERS_TABLE.' WHERE user_id='.(int)$id;
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	return $row['group_id'];
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	global $user,$auth;
	$user->session_begin();
	$auth->acl($user->data);
	return $user->data['is_registered'];
}
function hook_get_login(){
	// return based on forum login the nick and the uid, in an array as shown
	global $user;
	$nick = $user->data['username'];
	$uid = $user->data['user_id'];
	return array(
		'nick' => $nick,
		'uid' => (int)$uid
	);
}
?>
