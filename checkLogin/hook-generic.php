<?php
// Do initial setup stuff here

function hook_is_op($id){
	// $id is the int of the user, should return a boolean if this user is a chat op
	
	return false;
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	
	return $n;
}
function hook_may_chat(){
	// return true/false if the user, based on cookies/forum/etc, may chat
	
	return true;
}
function hook_get_login(){
	// return based on forum login the nick and the uid, in an array as shown
	$nick = 'Sorunome';
	$uid = 1;
	return array(
		'nick' => $nick,
		'uid' => (int)$uid
	);
}
?>
