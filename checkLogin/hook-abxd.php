<?php
// Do initial setup stuff here
chdir('../../..');
include('lib/common.php');

function hook_is_op($id){
	$user = Fetch(Query("select * from {users} where id={0}", $id));
	return $user['powerlevel']>=1;
}
function hook_get_color_nick($n,$id){
	// $n is the nick, $id is the user id, return a string (HTML) how the nick color should look like
	$user = Query("SELECT u.(_userfields) FROM {users} u WHERE u.id={0}", $id);
	if(NumRows($user)){
		$colors = array(
			'nc0x' => '#888888',
			'nc1x' => '#888888',
			'nc2x' => '#888888',
			'nc00' => '#97ACEF',
			'nc10' => '#F185C9',
			'nc20' => '#7C60B0',
			'nc01' => '#D8E8FE',
			'nc11' => '#FFB3F3',
			'nc21' => '#EEB9BA',
			'nc02' => '#AFFABE',
			'nc12' => '#C762F2',
			'nc22' => '#47B53C',
			'nc03' => '#FFEA95',
			'nc13' => '#C53A9E',
			'nc23' => '#F0C413',
			'nc04' => '#5555FF',
			'nc14' => '#FF5588',
			'nc24' => '#FF55FF',
			'nc05' => '#FF0000',
			'nc15' => '#FF0000',
			'nc25' => '#FF0000'
		);
		
		$s = UserLink(getDataPrefix(Fetch($user), "u_"));
		return '<a target="_top" style="color:'.$colors[substr($s,strpos($s,'class="')+7,4)].';" '.substr($s,2);
	}
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
