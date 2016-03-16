<?php
// Do initial setup stuff here
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : realpath(dirname(__FILE__)).'/../../../../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
if(isset($request)){
	$request->enable_super_globals();
}

function hook_is_op($id){
	// $id is the int of the user, should return a boolean if this user is a chat op
	global $auth;
	$a = $auth->acl_raw_data($id,'m_oirc_op');
	return isset($a[$id]) && isset($a[$id][0]) && isset($a[$id][0]['m_oirc_op']) && $a[$id][0]['m_oirc_op'];
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	global $db,$user,$auth,$phpEx;
	$user->session_begin();
	$auth->acl($user->data);
	$sql = $db->sql_build_query('SELECT', array(
		'SELECT' => 'user_id,username,user_colour',
		'FROM' => array(USERS_TABLE => 'u'),
		'WHERE' => $db->sql_in_set('user_id',$id),
	));
	
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if($row){
		return '<a target="_top"'.substr(get_username_string('full',$row['user_id'],$row['username'],$row['user_colour'],$n,generate_board_url().'/memberlist.'.$phpEx.'?mode=viewprofile&'),2);
	}
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	global $user,$auth;
	$user->session_begin();
	$auth->acl($user->data);
	return $auth->acl_get('u_oirc_view');
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
