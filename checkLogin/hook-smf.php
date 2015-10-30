<?php
// Do initial setup stuff here

$ssi_guest_access = true;
require(realpath(dirname(__FILE__)).'/../SSI.php');

function hook_is_op($id){
	global $smcFunc,$memberContext,$modSettings;
	$request = $smcFunc['db_query']('',"SELECT id_member,id_group,id_post_group,additional_groups FROM {db_prefix}members WHERE id_member = {int:id_member} LIMIT 1",array('id_member' => $id) );
	$res = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	if($res){
		$id = (int)$res['id_member'];
		loadMemberData($id);
		loadMemberContext($id);
		if($memberContext[$id]['is_banned'] || $memberContext[$id]['is_guest']){
			return false;
		}
		$groups = array_unique(array_merge(array($res['id_group'],$res['id_post_group']),explode(',',$res['additional_groups'])));
		if(in_array(1,$groups)){ // we are admin!
			return true;
		}
		
		$groups = implode(',',$groups);
		$request = $smcFunc['db_query']('',"SELECT permission,add_deny FROM {db_prefix}permissions WHERE id_group IN ({string:groups})",array('groups' => $groups));
		$ret = array();
		$rem = array();
		while($res = $smcFunc['db_fetch_assoc']($request)){
			if(empty($res['add_deny'])){
				$rem[] = $res['permission'];
			}else{
				$ret[] = $res['permission'];
			}
		}
		$smcFunc['db_free_result']($request);
		if(!empty($modSettings['permission_enable_deny'])){
			$ret = array_diff($ret,$rem);
		}
		return in_array('oirc_is_op',$ret);
	}
	return false;
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
	return allowedTo('oirc_can_view');
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
