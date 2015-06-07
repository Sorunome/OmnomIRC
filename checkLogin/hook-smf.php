<?php
// Do initial setup stuff here

$ssi_guest_access = true;
require(realpath(dirname(__FILE__)).'/../SSI.php');

function hook_get_group($id){
	// $id is the int of the user, should return a string to identify the group
	global $memberContext;
	loadMemberData($id);
	loadMemberContext($id);
	return $memberContext[$id]['group'];
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	global $smcFunc,$memberContext;
	$request = $smcFunc['db_query']('',"SELECT id_member FROM {db_prefix}members WHERE id_member = {int:id_member} LIMIT 1",array('id_member' => $id) );
	$res = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	
	if($res){
		$id = (int)$res['id_member'];
		loadMemberData($id);
		loadMemberContext($id);
		$n = '<a style="color:'.$memberContext[$id]['group_color'].';border-color:'.$memberContext[$id]['group_color'].'" target="_top" '.substr($memberContext[$id]['link'],2);
	}
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	global $user_info;
	return $user_info['name']!='' && !$user_info['is_guest'] && !is_not_banned();
}
function hook_get_login(){
	// return based on forum login the nick and the uid, in an array as shown
	global $user_info,$context;
	$nick = html_entity_decode($user_info['name']);
	$uid = $context['user']['id'];
	return array(
		'nick' => $nick,
		'uid' => (int)$uid
	);
}
?>
