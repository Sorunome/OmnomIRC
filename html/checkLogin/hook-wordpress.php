<?php
// Do initial setup stuff here
require( dirname(__FILE__) . '/../wp-load.php' );

function hook_is_op($id){
	// $id is the int of the user, should return a boolean if this user is a chat op
	wp_set_current_user($id);
	$user = wp_get_current_user();
	return isset($user->caps['administrator'])?$user->caps['administrator']:false;
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	$user = wp_get_current_user();
	return $user->ID != 0;
}
function hook_get_login(){
	// return based on forum login the nick and the uid, in an array as shown
	$user = wp_get_current_user();
	$nick = $user->data->display_name;
	$uid = $user->data->ID;
	return array(
		'nick' => $nick,
		'uid' => (int)$uid
	);
}
?>
